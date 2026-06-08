# 商品詳細情報取得機能テストの技術解説

---

## 1. 全体の処理概要

テストとは、「コードが意図した通りに動くか自動で確認する仕組み」です。手動でブラウザを操作して確認する代わりに、コードがその操作を代行します。

```
【テストの流れ（全体像）】

  ┌─────────────────────────────────────────────────────┐
  │                ItemDetailTest.php                    │
  │           「テスト用の司令塔」                         │
  │                                                     │
  │  ① テスト用データを準備する                           │
  │     User::factory()->create()  → ユーザー作成         │
  │     Item::factory()->create()  → 商品作成             │
  │     Category::factory()->create() → カテゴリ作成      │
  │     Comment::create()          → コメント作成         │
  │     Like::create()             → いいね作成           │
  │     $item->categories()->attach() → 商品とカテゴリを紐付け │
  └───────────────────┬─────────────────────────────────┘
                      │ ② GET /items/{id} にリクエスト送信
                      ▼
  ┌─────────────────────────────────────────────────────┐
  │             Laravel アプリケーション本体               │
  │                                                     │
  │  web.php（ルーティング）                              │
  │   └─ Route::get('/items/{item}', [ItemController])  │
  │         ↓                                           │
  │  ItemController@show                                │
  │   └─ $item->load(['categories', 'comments.user'])   │
  │   └─ $item->loadCount(['likes', 'comments'])        │
  │   └─ view('items.show', compact('item', 'isLiked')) │
  │         ↓                                           │
  │  show.blade.php（ビュー）                            │
  │   └─ 商品情報をHTMLに変換して返す                     │
  └───────────────────┬─────────────────────────────────┘
                      │ ③ HTMLレスポンスを返す
                      ▼
  ┌─────────────────────────────────────────────────────┐
  │                ItemDetailTest.php                    │
  │                                                     │
  │  ④ レスポンスを検証する                               │
  │     assertStatus(200)     → 正常に表示されたか        │
  │     assertSee('商品名')   → 商品名が含まれているか     │
  │     assertSee('ブランド') → ブランド名が含まれているか  │
  │     assertSee('¥3,000')  → 価格が含まれているか       │
  │     ......（以下11項目続く）                          │
  └─────────────────────────────────────────────────────┘
```

商品詳細ページのテストでは、「データを作る → ページを開く → 画面に正しく表示されているか確認する」という3ステップを自動化しています。

---

## 2. 各ファイルの役割

### `tests/Feature/ItemDetailTest.php` — テスト本体

商品詳細ページの表示内容を確認するテストをまとめるファイルです。

```
tests/
  Feature/（機能テスト）
    ├── LoginTest.php       ← ログイン機能のテスト
    ├── RegisterTest.php    ← 会員登録機能のテスト
    ├── ItemListTest.php    ← 商品一覧機能のテスト
    └── ItemDetailTest.php  ← 商品詳細機能のテスト（今回作成）
```

**Feature テスト**とは、複数のファイルが連携する機能全体を確認するテストです。今回のテストは「ルーティング → コントローラー → モデル → ビュー」という一連の流れを一括で検証します。

---

### `database/factories/CategoryFactory.php` — カテゴリのテストデータ生成器

テスト用のカテゴリデータを簡単に作るためのファクトリです。今回新たに作成しました。

```php
class CategoryFactory extends Factory
{
    public function definition()
    {
        return [
            'name' => $this->faker->unique()->word(),
            //       ↑Fakerライブラリ  ↑重複しない ↑ランダムな単語
        ];
    }
}
```

元々 `ItemFactory.php` / `UserFactory.php` / `CommentFactory.php` は存在していましたが、`CategoryFactory.php` は存在しませんでした。`Category` モデルに `use HasFactory` が書かれているのにファクトリがない状態だったため、テストで `Category::factory()` を使うために作成しています。

---

### `app/Models/Item.php` — 商品モデル（既存）

商品テーブルとそれに関連するテーブルとの関係を定義しています。テストはこのリレーションを通じてデータを組み立てます。

```php
class Item extends Model
{
    public function likes()
    {
        return $this->hasMany(Like::class);      // 1商品 → 複数のいいね
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);   // 1商品 → 複数のコメント
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'item_category');
        // 商品 ↔ カテゴリ（中間テーブル item_category を通じた多対多）
    }
}
```

---

### `app/Http/Controllers/ItemController.php` — テスト対象のコントローラー（既存）

テストがリクエストを送る先です。`show` メソッドが商品詳細データを取得してビューに渡します。

```php
public function show(Item $item)
{
    $item->load([
        'categories',        // ← カテゴリを取得
        'comments.user',     // ← コメントとそのユーザーを取得
    ])->loadCount([
        'likes',             // ← いいね数を取得
        'comments',          // ← コメント数を取得
    ]);

    $isLiked = auth()->check()
        ? $item->likes()->where('user_id', auth()->id())->exists()
        : false;

    return view('items.show', compact('item', 'isLiked'));
}
```

---

### `resources/views/items/show.blade.php` — 最終的にHTMLを生成するビュー（既存）

コントローラーから渡されたデータをHTMLに変換します。テストの `assertSee()` は、このビューが生成したHTMLの中に指定した文字列が含まれているかを確認します。

```php
// ビューがHTMLに変換するもの（一部抜粋）
<h1>{{ $item->name }}</h1>                  → テスト商品名
<p>{{ $item->brand_name }}</p>              → テストブランド
<p>¥{{ number_format($item->price) }}</p>   → ¥3,000
<span>{{ $category->name }}</span>          → テストカテゴリ
<span>{{ $comment->content }}</span>        → これはテストコメントです。
```

---

### `tests/TestCase.php` — テストの共通基盤（既存）

全テストクラスの親クラスです。`CreatesApplication` トレイトがテスト用にLaravelアプリを起動する仕組みを提供します。

```php
abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
}
```

---

### `phpunit.xml` — PHPUnitの設定ファイル（既存）

テスト実行時の環境設定です。

| 設定 | 値 | 意味 |
|---|---|---|
| `APP_ENV` | `testing` | テスト環境であることを宣言（CSRF無効化など） |
| `SESSION_DRIVER` | `array` | セッションをメモリ上に保存（テスト間で干渉しない） |
| `DB_CONNECTION` | `sqlite` / `mysql` | テスト専用のDB接続 |

---

## 3. 各関数の意味

### `use RefreshDatabase`

```php
class ItemDetailTest extends TestCase
{
    use RefreshDatabase;  // ← これ
```

**テストを実行するたびにデータベースをリセット**するトレイト（機能追加の仕組み）です。

```
テスト1（全情報表示テスト）実行
 → DBにユーザー・商品・カテゴリ・コメント・いいねを作成
 → テスト終了後にDBを全リセット ←────────────────┐
                                                 │ RefreshDatabase が自動実行
テスト2（複数カテゴリテスト）実行                    │
 → 綺麗なDBでスタート ───────────────────────────┘
```

これがないと、テスト1で作ったデータがテスト2に残ってしまいます。

---

### `User::factory()->create([...])`

```php
$seller = User::factory()->create(['name' => '出品者テスト']);
```

**テスト用のユーザーをデータベースに作成**します。

```
factory()      → UserFactory.php の設定を使ってダミーデータを準備
create([...])  → DBに実際に保存する（上書きしたい項目は配列で指定）
```

`UserFactory.php` の定義にある `faker` がランダムなメール・パスワードを自動生成するため、テストごとに固有のユーザーが作れます。`create()` の引数に指定した項目（今回は `name`）だけが上書きされ、残りはファクトリのデフォルト値が使われます。

---

### `Item::factory()->create([...])`

```php
$item = Item::factory()->create([
    'user_id'     => $seller->id,
    'name'        => 'テスト商品名',
    'brand_name'  => 'テストブランド',
    'description' => 'これはテスト用の商品説明です。',
    'price'       => 3000,
    'image_path'  => 'storage/items/dummy.jpg',
    'condition'   => '良好',
]);
```

**テスト用の商品をデータベースに作成**します。

`assertSee()` で確認する文字列は、ここで指定した値と一致させる必要があります。例えば価格を `3000` にしたなら、ビューでは `number_format(3000)` = `3,000` が表示されるため、テストでは `¥3,000` を確認します。

```
price: 3000 → number_format(3000) → "3,000" → ビューで "¥3,000(税込)" と表示
                                                ↑ assertSee('¥3,000') で確認
```

---

### `Category::factory()->create([...])`

```php
$category = Category::factory()->create(['name' => 'テストカテゴリ']);
```

**テスト用のカテゴリをデータベースに作成**します。

今回作成した `CategoryFactory.php` が使われます。`name` を固定値で指定することで、後の `assertSee('テストカテゴリ')` と照合できます。

---

### `$item->categories()->attach($category->id)`

```php
$item->categories()->attach($category->id);
```

**商品とカテゴリを中間テーブルで紐付け**ます。

商品とカテゴリは「多対多」の関係です（1商品に複数カテゴリ、1カテゴリに複数商品）。

```
items テーブル          item_category テーブル    categories テーブル
┌──────────────┐       ┌──────────────────────┐  ┌──────────────────┐
│ id │ name    │       │ item_id │ category_id │  │ id │ name        │
├────┼─────────┤       ├─────────┼─────────────┤  ├────┼─────────────┤
│  1 │テスト商品│       │       1 │           1 │  │  1 │テストカテゴリ│
└──────────────┘       └──────────────────────┘  └──────────────────┘
        ↑                    attach() が作る            ↑
        └────────────────────────────────────────────┘
```

`attach()` は `item_category` テーブルに「item_id = 1, category_id = 1」という行を挿入します。

複数カテゴリのテストでは配列で一括指定できます。

```php
$item->categories()->attach([$category1->id, $category2->id, $category3->id]);
//                           ↑ 3つのカテゴリを一度に紐付け
```

---

### `Comment::create([...])`

```php
Comment::create([
    'user_id' => $commenter->id,
    'item_id' => $item->id,
    'content' => 'これはテストコメントです。',
]);
```

**テスト用のコメントをデータベースに直接作成**します。

`Comment::factory()->create()` ではなく `Comment::create()` を使っているのは、`CommentFactory.php` の実装が `User::inRandomOrder()->first()->id` を使うため、ランダムなユーザーが割り当てられてしまうからです。テストでは「どのユーザーのコメントか」を正確に指定したいため、`create()` で直接値を指定しています。

---

### `Like::create([...])`

```php
Like::create(['user_id' => User::factory()->create()->id, 'item_id' => $item->id]);
Like::create(['user_id' => User::factory()->create()->id, 'item_id' => $item->id]);
```

**テスト用のいいねを2件作成**します。

いいね数を確認するテストのため、「誰がいいねしたか」よりも「いいねが2件あること」が重要です。そのため `User::factory()->create()->id` でその場でユーザーを作り、即座に `user_id` として使っています。

```
いいね作成の流れ：
User::factory()->create()  → 新しいユーザーをDBに保存 → id=2 が返る
  .id                      → 2 を取得
Like::create([user_id: 2, item_id: 1])  → いいねをDBに保存
```

---

### `$this->get(route('items.show', $item->id))`

```php
$response = $this->get(route('items.show', $item->id));
```

**ブラウザで商品詳細ページを開く操作をコードで模倣**します。

```
route('items.show', $item->id)
 ↓ 例：$item->id が 1 の場合
'/items/1'  というURLを生成

$this->get('/items/1')
 ↓
Laravel アプリケーションに GET /items/1 リクエストを送信
 ↓
ItemController::show() が実行される
 ↓
$response にHTMLレスポンスが入る
```

`route('items.show', $item->id)` は `web.php` で定義された名前付きルートを使ってURLを生成しています。URLをハードコードする（`'/items/1'` と書く）よりも、ルート名を使うほうがURLが変わっても修正が1箇所で済むため推奨されます。

---

### `$response->assertStatus(200)`

```php
$response->assertStatus(200);
```

**HTTPレスポンスのステータスコードが200（正常）であるか**を確認します。

| ステータスコード | 意味 |
|---|---|
| 200 | OK（正常にページが表示された） |
| 302 | リダイレクト |
| 404 | Not Found（ページが見つからない） |
| 500 | Internal Server Error（サーバーエラー） |

ページが正常に表示されたことを最初に確認してから、内容の確認に進みます。

---

### `$response->assertSee('文字列')`

```php
$response->assertSee('テスト商品名');
$response->assertSee('¥3,000');
$response->assertSee('コメントユーザー');
```

**レスポンスのHTMLに指定した文字列が含まれているか**を確認します。

```
show.blade.php が生成するHTML（一部）：
<h1 class="item-detail__name">テスト商品名</h1>
<p class="item-detail__price">¥3,000<span>(税込)</span></p>
<span class="item-detail__comment-name">コメントユーザー</span>

assertSee('テスト商品名') → HTML全体を検索 → 見つかれば ✓
assertSee('¥3,000')      → HTML全体を検索 → 見つかれば ✓
```

部分一致で検索するため、タグやクラス名は気にする必要がありません。「その文字列がHTMLのどこかに存在すればパス」です。

---

## 4. なぜこの実装なのか（設計意図）

### なぜ1テスト＝1つのシナリオにするのか

```php
// 今回の実装：シナリオごとにテストを分ける
public function item_detail_page_displays_all_required_information() { ... }
public function item_detail_page_displays_multiple_selected_categories() { ... }
```

1テスト1シナリオにする理由：

- テストが失敗したとき「何の確認が壊れたか」がテスト名からすぐにわかる
- `RefreshDatabase` によるDBリセットが各テストで正しく動く
- 一方のシナリオのデータ（例：1カテゴリ）が他方（3カテゴリ）に干渉しない

---

### なぜ `assertSee` で確認する文字列を「固定値」にするのか

```php
// テストデータ作成時に固定値を指定
$item = Item::factory()->create([
    'name'  => 'テスト商品名',   // ← 固定値
    'price' => 3000,             // ← 固定値
]);

// 確認するときも同じ固定値を使う
$response->assertSee('テスト商品名');  // ← 一致することが保証できる
$response->assertSee('¥3,000');       // ← 3000 → number_format → 3,000 と計算可能
```

`faker` のランダム値を使うと、「何が表示されるべきか」が実行ごとに変わってしまいます。テストで確認する値は、テストデータ作成時に決めた値と必ず一致しなければならないため、`assertSee` で確認する項目は固定値で指定します。

---

### なぜ価格のテストで `¥3,000` と書くのか

```php
'price' => 3000,  // ← 数値でDBに保存

// ビューでの表示
¥{{ number_format($item->price) }}(税込)
// ↓ 実際のHTML
¥3,000(税込)
```

`number_format(3000)` が `'3,000'`（カンマ区切り）になることを考慮して `¥3,000` と書いています。`¥3000`（カンマなし）では一致しないためテストが失敗します。

```
NG: assertSee('¥3000')   → '¥3000' はHTMLに存在しない
OK: assertSee('¥3,000')  → '¥3,000' はHTMLに存在する ✓
```

---

### なぜいいねを2件作成するのか

```php
Like::create([...]);  // 1件目
Like::create([...]);  // 2件目

// ビュー側
<span class="item-detail__meta-count">{{ $item->likes_count }}</span>
// → "2" と表示される
```

`$response->assertSee('2')` でいいね数を確認しています。1件のみだと `'1'` という数字が他の箇所（例：コメント数表示の `コメント(1)`）にも現れるため、いいね数の確認として曖昧になります。2件にすることで「2という数字がいいねカウントとして存在する」という確認の精度が上がります。

---

### なぜコメントのユーザー名に固定値を使うのか

```php
$commenter = User::factory()->create(['name' => 'コメントユーザー']);
//                                   ↑ 名前を固定

Comment::create([
    'user_id' => $commenter->id,   // ← このユーザーのコメントとして作成
    ...
]);

$response->assertSee('コメントユーザー');  // ← 固定値で確認できる
```

`User::factory()->create()` のみでは `faker` がランダムな日本語名を生成するため、「どんな名前が表示されるべきか」がテスト実行時まで不明です。`name` を固定値で指定することで、「このユーザーのコメントがページに表示されるか」を確実に確認できます。

---

### なぜ複数カテゴリテストを独立させるのか

```php
// テスト1（全情報表示）: 1カテゴリのみ
$category = Category::factory()->create(['name' => 'テストカテゴリ']);
$item->categories()->attach($category->id);

// テスト2（複数カテゴリ）: 3カテゴリ
$item->categories()->attach([$category1->id, $category2->id, $category3->id]);
```

「全情報の表示テスト」の目的は「11種類の情報がすべて表示されること」の確認です。カテゴリの複数表示という追加の確認まで含めると、テストの意図が分散してしまいます。「複数カテゴリが正しく表示される」という独立したシナリオを分けて書くことで、各テストの目的が明確になります。

---

## 5. よくあるミスや注意点

### ミス1：`use RefreshDatabase` を忘れる

```php
// NG
class ItemDetailTest extends TestCase
{
    // use RefreshDatabase; ← 忘れた！
```

1回目のテスト実行は通っても、2回目以降に問題が起きることがあります。前のテストで作ったデータが残り続けるため、意図せず件数が増えたりします。

```
1回目: いいね2件を作成 → likes_count = 2 ✓
2回目: いいね2件を追加 → likes_count = 4（前回の2件が残っている！） ✗
```

---

### ミス2：価格確認で `number_format` を考慮しない

```php
// NG：カンマなしで確認してしまう
$response->assertSee('¥3000');   // HTMLには '¥3,000' しかない → 失敗

// OK：number_format の結果（カンマ区切り）で確認する
$response->assertSee('¥3,000');
```

ビューで `¥{{ number_format($item->price) }}` と書いてあるため、`3000` は `3,000` に変換されます。`assertSee` に渡す文字列はHTMLに実際に出力される形式と一致させる必要があります。

---

### ミス3：`attach()` を忘れて空のカテゴリ表示をテストしてしまう

```php
// NG：attach() を書き忘れる
$category = Category::factory()->create(['name' => 'テストカテゴリ']);
// $item->categories()->attach($category->id); ← 忘れた！

$response->assertSee('テストカテゴリ');  // → カテゴリが紐付いていないので失敗
```

`Category::factory()->create()` はカテゴリをDBに保存するだけです。商品とカテゴリを紐付けるには `attach()` が必要です。

```
Category::factory()->create()  → categories テーブルに行を追加するだけ
$item->categories()->attach()  → item_category テーブルに行を追加（紐付け）
```

この2行は必ずセットで書く必要があります。

---

### ミス4：`Comment::factory()->create()` をそのまま使うと確認できない

```php
// NG：ランダム値で作ると assertSee で確認できない
$comment = Comment::factory()->create(['item_id' => $item->id]);
// faker が生成したランダムな content はテスト側で把握できない

// さらに問題のある NG：CommentFactory がランダムな item_id を使う
$comment = Comment::factory()->create();  // item_id が $item と違う商品を向く可能性
```

`CommentFactory.php` の `definition()` は `Item::inRandomOrder()->first()->id` を使っています。これはDBにある商品からランダムに選ぶため、今テストしている商品のコメントにならない可能性があります。

```php
// OK：Comment::create() で確実に item_id と content を固定する
Comment::create([
    'user_id' => $commenter->id,
    'item_id' => $item->id,              // ← 確実にこの商品に紐付ける
    'content' => 'これはテストコメントです。',  // ← assertSee で確認できる固定値
]);
```

---

### ミス5：ルート名でなくURLをハードコードする

```php
// NG：URLを直接書く
$response = $this->get('/items/' . $item->id);

// OK：名前付きルートを使う
$response = $this->get(route('items.show', $item->id));
```

`web.php` でルートのURLを `/item/{item}` に変更したとき、ハードコードしたテストはすべて修正が必要になります。`route()` を使えば `web.php` の変更が自動で反映されます。

---

### ミス6：テストメソッドを `/** @test */` なしで `test_` 以外の名前にする

```php
// NG：test_ で始まらず @test もない → PHPUnit に認識されない
public function item_detail_displays_name()
{
    // このメソッドはテストとして実行されない
}

// OK：test_ で始める
public function test_item_detail_displays_name() { ... }

// または @test アノテーションを付ける
/** @test */
public function item_detail_displays_name() { ... }
```

今回のテストは `/** @test */` アノテーション方式を採用しています。PHPUnit は `test_` で始まるメソッド、または `@test` アノテーションが付いたメソッドだけをテストとして実行します。

---

### ミス7：`assertSee` の確認対象が広すぎる

```php
// NG：'1' や '2' のような1桁数字は別の場所にも現れやすい
$response->assertSee('1');  // ← コメント数？ID？ページネーション？ どれか不明

// OK：文脈が特定できる文字列で確認する
$response->assertSee('コメント(1)');  // ← "コメント(1)" という文字列全体で確認
```

`assertSee` は「HTMLのどこかに含まれれば通過」なので、短すぎる文字列では意図せず通過してしまうことがあります。確認対象は「ビューのその箇所にしか出ない文字列」を選ぶことが重要です。

---

## まとめ

```
ItemDetailTest.php の全体構造

use RefreshDatabase
  → 毎テスト後にDBをリセットしてデータの干渉を防ぐ

テストデータ準備
  User::factory()->create()           → 出品者・コメントユーザーを作成
  Item::factory()->create([固定値])   → テストしたい商品を作成
  Category::factory()->create()       → カテゴリを作成
  $item->categories()->attach()       → 商品とカテゴリを中間テーブルで紐付け
  Comment::create([固定値])           → コメントを固定内容で作成
  Like::create()                      → いいねを作成

リクエスト送信
  $this->get(route('items.show', id)) → ブラウザでページを開く操作を模倣

レスポンス検証
  assertStatus(200)                   → ページが正常に表示されたか
  assertSee('テスト商品名')            → 商品名が表示されているか
  assertSee('¥3,000')                 → 価格（カンマ区切り）が表示されているか
  assertSee('コメント(1)')             → コメント数が表示されているか
  assertSee('コメントユーザー')         → コメントしたユーザー名が表示されているか
  ...（11項目）
```

テストの本質は「期待通りの状態か確認する」ことです。今回のテストは「正しいデータを用意し、商品詳細ページを開いたとき、そのデータがすべて画面に表示されるか」を自動で検証しています。
