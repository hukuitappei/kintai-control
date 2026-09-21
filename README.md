# 勤怠管理アプリ

一般ユーザーが出勤・休憩・退勤を打刻し、勤怠の修正申請を行い、管理者が勤怠の確認・修正・申請の承認を行うアプリケーションです。

## 環境構築

Docker（Laravel Sail）でビルドし、マイグレーションとシーディングまで行います。

### Docker ビルド

1. `git clone https://github.com/hukuitappei/kintai-control.git`
2. `cd kintai-control`
3. `cp .env.example .env`
4. 依存パッケージのインストール（初回のみ。`vendor/` を作るために Docker で実行します）
   ```bash
   docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd):/var/www/html" -w /var/www/html \
     laravelsail/php82-composer:latest composer install --ignore-platform-reqs
   ```
5. `./vendor/bin/sail up -d --build`

※ MySQL がポート 3306 の競合で起動しない場合は、`.env` に `FORWARD_DB_PORT=3307` を追加して、もう一度 `./vendor/bin/sail up -d` を実行してください。

### Laravel 環境構築

1. `./vendor/bin/sail artisan key:generate`
2. `./vendor/bin/sail artisan migrate`
3. `./vendor/bin/sail artisan db:seed`
4. `./vendor/bin/sail npm install`
5. `./vendor/bin/sail npm run dev`（開発中は起動したままにしてください）

## 使用技術（実行環境）

- PHP 8.2
- Laravel 10.x（Fortify / Sanctum）
- MySQL 8.4
- Laravel Sail（Docker）
- Vite

## ER図

（ER図は、テーブル設計の完成後に追加します）

## URL

- 開発環境: http://localhost/
- 管理者ログイン: http://localhost/admin/login
- phpMyAdmin: http://localhost:8080/
- Mailpit: http://localhost:8025/

## ログイン情報

| 種別 | メールアドレス | パスワード |
|---|---|---|
| 一般ユーザー（user1） | user1@example.com | password |
| 一般ユーザー（user2） | user2@example.com | password |
| 管理者（user3） | user3@example.com | password |
