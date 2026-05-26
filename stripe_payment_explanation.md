# Stripe 決済機能の技術解説

---

## 1. 全体の処理概要

Stripe 決済は「**自分のサーバー → Stripe のサーバー → 自分のサーバー**」という流れで動きます。決済の核心部分は Stripe 側が担当するため、自分のアプリはその前後だけを処理します。

### 画面表示の流れ

```
ブラウザで /purchase/{item} にアクセス
    ↓
① routes/web.php が PurchaseController@show に振り分ける
    ↓
② PurchaseController::show() がセッションから住所を取得して purchase.blade.php に渡す
    ↓
③ purchase.blade.php が商品情報・支払い方法・配送先フォームを表示
    ↓
④ 画面表示完了
```

### 「購入する」ボタンを押した後の流れ

```
支払い方法を選んで「購入する」ボタン押下
    ↓
① POST /purchase/{item} を送信
    ↓
② PurchaseRequest.php がバリデーション（入力チェック）を実行
        ❌ NG → エラーメッセージを付けて purchase 画面に戻す
        ✅ OK → 次へ
    ↓
③ PurchaseController::store() が購入データをセッションに保存
    ↓
④ Stripe の Checkout Session（決済セッション）を作成する
    ↓
⑤ ブラウザが Stripe の決済画面（外部サイト）にリダイレクト
    ↓
⑥ ユーザーが Stripe の画面でカード番号など入力して支払い完了
    ↓
⑦ Stripe が自動的に /purchase/{item}/success にリダイレクト
    ↓
⑧ PurchaseController::success() が Stripe に「本当に支払い済みか」を確認
    ↓
⑨ Purchase レコードをデータベースに保存
    ↓
⑩ 商品一覧画面（/）にリダイレクト
```

---

## 2. 各ファイルの役割

| ファイル | 役割 |
|---|---|
| `routes/web.php` | URLと処理の対応を定義する交通整理係 |
| `config/services.php` | Stripe のAPIキーなど外部サービスの設定を一元管理する |
| `.env` | 実際のAPIキー値を保持する環境設定ファイル |
| `app/Http/Requests/PurchaseRequest.php` | 入力チェック係。支払い方法・住所が入力されているか確認する |
| `app/Http/Controllers/PurchaseController.php` | 決済処理の司令塔。Stripe との通信・DBへの保存を制御する |
| `resources/views/items/purchase.blade.php` | ユーザーが見る購入確認画面のHTML |
| `vendor/stripe/stripe-php/` | Stripe 公式ライブラリ。Stripe API との通信を簡単にするコードの集まり |

---

## 3. 各関数・構文の意味

### config/services.php

```php
'stripe' => [
    'key'    => env('STRIPE_KEY'),
    'secret' => env('STRIPE_SECRET'),
],
```

**`env('STRIPE_KEY')`** は `.env` ファイルから `STRIPE_KEY=pk_test_xxxx` の値を読み取る関数です。
APIキーを直接コードに書くと GitHub などに公開されてしまうため、`.env` に分離して `env()` 経由で読みます。

アプリ内では `config('services.stripe.secret')` のように階層で参照できます。

---

### PurchaseController::store()

```php
public function store(PurchaseRequest $request, Item $item): RedirectResponse
```

**引数の意味：**
- `PurchaseRequest $request` — フォームの送信値が入っている。型を `PurchaseRequest` と指定するだけで Laravel が自動でバリデーションを実行してくれる
- `Item $item` — URLの `{item}` 部分の数字からデータベースのレコードを自動で取得してくれる（ルートモデルバインディングという仕組み）

---

```php
session(['purchase_stripe_data' => [
    'item_id'        => $item->id,
    'payment_method' => $request->input('payment_method'),
    'postal_code'    => $data['postal_code'],
    'address'        => $data['address'],
    'building'       => $data['building'] ?? null,
]]);
```

**なぜセッションに保存するのか？**

購入データを今すぐ DB に保存してはいけません。この時点ではまだユーザーが Stripe の画面で支払いを完了していないからです。
支払い前にレコードを作ると「支払いキャンセルしたのに購入済み扱いになる」バグが起きます。

`session()` はサーバー上に一時的にデータを保持する仕組みです。ユーザーが Stripe の画面から戻ってきた後に取り出して使います。

---

```php
Stripe::setApiKey(config('services.stripe.secret'));
```

Stripe ライブラリに「どのアカウントで操作するか」を伝える設定です。
`sk_test_xxxx` のシークレットキーを渡すことで、そのアカウントの権限で API を呼び出せるようになります。

---

```php
$paymentMethodTypes = $request->input('payment_method') === 'カード支払い'
    ? ['card']
    : ['konbini'];
```

**三項演算子** で支払い方法に応じて Stripe に渡す値を切り替えています。

| 画面の選択 | Stripe に渡す値 |
|---|---|
| カード支払い | `['card']` |
| コンビニ支払い | `['konbini']` |

Stripe 側は `payment_method_types` の値によって、表示する決済UIを切り替えます。

---

```php
$checkoutSession = StripeSession::create([
    'payment_method_types' => $paymentMethodTypes,
    'line_items' => [[
        'price_data' => [
            'currency'     => 'jpy',
            'product_data' => [
                'name' => $item->name,
            ],
            'unit_amount'  => $item->price,
        ],
        'quantity' => 1,
    ]],
    'mode'        => 'payment',
    'success_url' => route('purchase.success', $item) . '?session_id={CHECKOUT_SESSION_ID}',
    'cancel_url'  => route('purchase.show', $item),
]);
```

`StripeSession::create()` は Stripe のサーバーに「この商品の決済ページを作ってください」とリクエストを送る関数です。

各パラメータの意味：

| パラメータ | 意味 |
|---|---|
| `payment_method_types` | 使える支払い方法（card や konbini）|
| `line_items` | 購入する商品のリスト（今回は1商品のみ）|
| `currency` | 通貨（`jpy` = 日本円）|
| `unit_amount` | 金額（日本円は**小数点なし**なので 1000 = 1,000円）|
| `mode` | `'payment'` は1回払い。定期課金なら `'subscription'` |
| `success_url` | 支払い成功後に Stripe が戻すURL |
| `cancel_url` | ユーザーがキャンセルしたときに戻すURL |

**`{CHECKOUT_SESSION_ID}`** は Stripe が自動で実際のセッションIDに置き換えてくれる特殊な文字列です。`success_url` に付けることで、成功後にどのセッションの支払いか追跡できます。

---

```php
return redirect($checkoutSession->url);
```

Stripe が返してきた決済ページのURL（例：`https://checkout.stripe.com/pay/cs_test_xxxx`）にブラウザをリダイレクトします。

---

### PurchaseController::success()

```php
public function success(Item $item): RedirectResponse
```

Stripe が支払い完了後に `/purchase/{item}/success?session_id=cs_test_xxxx` にリダイレクトしてくる。このメソッドがその処理を担当します。

---

```php
$data = session('purchase_stripe_data');

if (!$data || $data['item_id'] !== $item->id) {
    return redirect()->route('items.index');
}
```

セッションに保存した購入データを取り出します。

セッションがない（直接このURLを開いた）または商品IDが一致しない場合は不正なアクセスと判断して商品一覧に追い返します。これは**二重購入や不正操作を防ぐ**ガードです。

---

```php
$sessionId = request()->query('session_id');
Stripe::setApiKey(config('services.stripe.secret'));
$stripeSession = StripeSession::retrieve($sessionId);
```

URLの `?session_id=cs_test_xxxx` からIDを取り出し、Stripe に「このセッションの詳細を教えて」と問い合わせます。

Stripe からのリダイレクトは**ブラウザ側で偽造できる**ため、「本当に支払いが完了したか」をサーバー間で確認することが重要です。

---

```php
if ($stripeSession->payment_status !== 'paid' && $stripeSession->payment_status !== 'no_payment_required') {
    return redirect()->route('purchase.show', $item);
}
```

Stripe から取得したセッションの `payment_status` を確認します。

| `payment_status` の値 | 意味 |
|---|---|
| `paid` | 支払い完了 |
| `unpaid` | 未払い |
| `no_payment_required` | 0円など支払い不要（テスト時に出ることがある）|

`paid` または `no_payment_required` 以外の場合は購入画面に戻します。

---

```php
Purchase::create([
    'user_id'        => auth()->id(),
    'item_id'        => $item->id,
    'payment_method' => $data['payment_method'],
    'postal_code'    => $data['postal_code'],
    'address'        => $data['address'],
    'building'       => $data['building'],
]);

session()->forget(['purchase_stripe_data', 'purchase_address']);
```

支払い確認が取れた**ここで初めて**DBに購入レコードを保存します。
その後セッションを削除して後片付けをします。

---

### routes/web.php

```php
Route::get('/purchase/{item}', [PurchaseController::class, 'show'])->name('purchase.show');
Route::post('/purchase/{item}', [PurchaseController::class, 'store'])->name('purchase.store');
Route::get('/purchase/{item}/success', [PurchaseController::class, 'success'])->name('purchase.success');
```

3つのルートの役割：

| HTTP メソッド | URL | 処理 | 呼ばれるタイミング |
|---|---|---|---|
| GET | `/purchase/{item}` | 購入確認画面を表示 | 購入ページを開いたとき |
| POST | `/purchase/{item}` | Stripe 決済セッション作成 | 「購入する」ボタン押下 |
| GET | `/purchase/{item}/success` | 支払い完了後の処理 | Stripe から戻ってきたとき |

---

## 4. なぜこの実装なのか（設計意図）

### なぜ Stripe Checkout（ホスト型）を使うのか？

決済処理を自前実装するには PCI DSS という国際的なセキュリティ基準を満たす必要があり、非常に複雑です。

Stripe Checkout は Stripe が管理する決済画面に移動する方式なので、**カード番号が自分のサーバーに届かない**。その結果：
- PCI DSS への準拠が不要
- カード情報の漏えいリスクをゼロにできる
- Stripe が決済UIのメンテナンスをしてくれる

### なぜ success_url に `session_id` を付けるのか？

`success_url` は**ユーザーのブラウザ**経由で来ます。ブラウザのURLは偽造できます。

`session_id` を使って Stripe に問い合わせ、サーバー間で支払い完了を確認することで、「URLを直接叩いて支払いをスキップする攻撃」を防ぎます。

### なぜ購入データをセッションに保存するのか？

ユーザーが Stripe の決済画面に移動している間、自分のサーバーは何もできません。しかしユーザーが戻ってきたときに「誰が何をどこに注文したか」を知る必要があります。

セッションはサーバー上にデータを保持できるため、Stripe の決済画面をまたいでデータを引き継げます。

### なぜ支払い確認前に Purchase レコードを作らないのか？

早まって DB に保存すると：
1. キャンセルしても購入済み扱いになる
2. 支払いエラーでも購入済みになる

支払い確認が取れた後に保存することで**DB の整合性を保ちます。**

---

## 5. よくあるミスや注意点

### シークレットキーと公開キーを逆に設定してしまう

```php
// ❌ NG（公開キーをサーバー側に使ってしまう）
Stripe::setApiKey(config('services.stripe.key'));  // pk_test_xxx

// ✅ OK（サーバー側はシークレットキー）
Stripe::setApiKey(config('services.stripe.secret'));  // sk_test_xxx
```

`pk_test_xxx`（公開キー）はブラウザのJavaScriptで使うもので、サーバー側 API の認証には `sk_test_xxx`（シークレットキー）が必要です。逆にするとエラーになります。

---

### `session_id` の検証をしないと不正購入できてしまう

```php
// ❌ NG（URLにアクセスするだけで購入が完了してしまう）
public function success(Item $item)
{
    Purchase::create([...]);
}

// ✅ OK（Stripe で支払い完了を確認してから保存）
$stripeSession = StripeSession::retrieve($sessionId);
if ($stripeSession->payment_status !== 'paid') {
    return redirect()->route('purchase.show', $item);
}
Purchase::create([...]);
```

検証なしだと、`/purchase/1/success` というURLを直接ブラウザで開くだけで、支払いなしに購入完了処理が走ります。

---

### コンビニ支払いは日本円・金額に制限がある

```php
// コンビニ支払い（konbini）は制限がある
// - 通貨は JPY のみ
// - 最低金額：120円
// - 最高金額：300,000円
// - 日本向け Stripe アカウントが必要
```

テスト環境で konbini を試すには Stripe の日本向けアカウント設定が必要です。使えない場合は `card` のみで開発を進めてください。

---

### `.env` のキーを Git にコミットしてしまう

```bash
# ❌ NG（.env を Git 管理してしまう）
git add .env
git commit -m "add stripe keys"

# ✅ OK（.env は .gitignore で除外されている）
# .env.example にキー名だけ書いておく
STRIPE_KEY=
STRIPE_SECRET=
```

Stripe のシークレットキーが GitHub に公開されると第三者に不正利用されます。Laravel の `.gitignore` はデフォルトで `.env` を除外していますが、意図せずコミットしないよう注意してください。

---

### success_url のドメインが本番と違う

```php
// ローカル開発時
'success_url' => 'http://localhost/purchase/1/success?session_id={CHECKOUT_SESSION_ID}'

// 本番環境では APP_URL を正しく設定する必要がある
// .env の APP_URL=https://your-domain.com が正しくないと
// Stripe からのリダイレクトが正しいURLに戻ってこない
```

`route()` ヘルパーは `APP_URL`（`.env`）を基準にURLを生成します。本番環境では `APP_URL` を実際のドメインに設定してください。

---

### `payment_method_options` が `[]` だと Stripe がエラーになる場合がある

```php
// ❌ 空配列を渡すと Stripe API がエラーになることがある
'payment_method_options' => [],

// ✅ カード支払いのときは payment_method_options を省略する
$params = [
    'payment_method_types' => ['card'],
    // ...
];
if ($request->input('payment_method') === 'コンビニ支払い') {
    $params['payment_method_options'] = ['konbini' => ['expires_after_days' => 3]];
}
$checkoutSession = StripeSession::create($params);
```

現在の実装では `[]` を渡していますが、Stripe のバージョンによっては空配列でエラーになる場合があります。コンビニ支払い時のみ設定するよう改善することで安全になります。
