# coachtechフリマ (フリマアプリ)

## 環境構築

**Dockerビルド**

1. `git clone git@github.com:estra-inc/confirmation-test-contact-form.git`
2. DockerDesktopアプリを立ち上げる
3. `docker-compose up -d --build`

> _MacのM1・M2チップのPCの場合、`no matching manifest for linux/arm64/v8 in the manifest list entries`のメッセージが表示されビルドができないことがあります。
> エラーが発生する場合は、docker-compose.ymlファイルの「nginx」「php」「mysql」「phpmyadmin」内に「platform」の項目を追加で記載してください_

```bash
mysql:
    platform: linux/x86_64(この文追加)
    image: mysql:8.0.26
    environment:
```

**Laravel環境構築**

1. `docker-compose exec php bash`
2. `composer install`
3. 「.env.example」ファイルを 「.env」ファイルに命名を変更。または、新しく.envファイルを作成
4. .envに以下の環境変数を追加

```text
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel_db
DB_USERNAME=laravel_user
DB_PASSWORD=laravel_pass

STRIPE_KEY=your_stripe_public_key
STRIPE_SECRET=your_stripe_secret_key

MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_FROM_ADDRESS=noreply@flea-market.example
```

5. アプリケーションキーの作成

```bash
php artisan key:generate
```

6. マイグレーションの実行

```bash
php artisan migrate
```

7. シーディングの実行

```bash
php artisan db:seed
```

8. ストレージのシンボリックリンク作成（商品画像・プロフィール画像の表示に必要）

```bash
php artisan storage:link
```

## 使用技術(実行環境)

- PHP 8.1
- Laravel 8.x
- MySQL 8.0.26
- Nginx 1.21.1
- MailHog（メール認証確認用）
- Stripe（決済処理）

## 実装機能

- 会員登録 / ログイン / ログアウト（Laravel Fortify）
- メール認証（MailHog でローカル受信確認）
- 商品一覧・キーワード検索（おすすめ / マイリスト タブ切替）
- 商品詳細表示（いいね数・コメント一覧）
- 商品出品（カテゴリ・商品状態・画像アップロード）
- いいね トグル
- コメント投稿
- 購入フロー（配送先変更 → Stripe Checkout → 購入完了）
- クレジットカード決済 / コンビニ決済（Stripe）
- マイページ（出品した商品・購入した商品）
- プロフィール編集（名前・住所・プロフィール画像）

## ER図

![ER図](src/docs/db/er.svg)

## URL

- 開発環境：http://localhost/
- phpMyAdmin：http://localhost:8080/
- MailHog：http://localhost:8025/
