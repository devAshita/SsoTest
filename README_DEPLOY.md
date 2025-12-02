# デプロイチェックリスト

## ディレクトリ構造の確認

以下のディレクトリとファイルが存在することを確認してください：

### 必須ディレクトリ
- ✅ `app/` - アプリケーションコード
- ✅ `bootstrap/` - ブートストラップファイル
- ✅ `config/` - 設定ファイル
- ✅ `database/` - マイグレーション
- ✅ `public/` - 公開ディレクトリ（ドキュメントルート）
- ✅ `resources/` - ビューファイル
- ✅ `routes/` - ルート定義
- ✅ `storage/` - ストレージ（書き込み可能）
- ✅ `vendor/` - Composer依存関係

### 必須ファイル
- ✅ `public/index.php` - エントリーポイント
- ✅ `public/.htaccess` - Apacheリライトルール
- ✅ `artisan` - Artisan CLI
- ✅ `composer.json` - Composer設定
- ✅ `.env` - 環境変数（サーバー側で作成）

### 重要なファイル
- ✅ `app/Http/Controllers/Controller.php` - ベースコントローラー
- ✅ `bootstrap/app.php` - アプリケーションブートストラップ

## サーバー側での設定

### 1. ディレクトリ権限の設定

```bash
chmod -R 755 storage bootstrap/cache
chmod -R 775 storage bootstrap/cache  # 書き込みが必要な場合
```

### 2. ストレージディレクトリの作成

```bash
mkdir -p storage/app/public
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs
chmod -R 775 storage
```

### 3. シンボリックリンクの作成（オプション）

```bash
php artisan storage:link
```

### 4. 環境変数の設定

`.env`ファイルを作成し、以下を設定：

```env
APP_NAME=SsoTest
APP_ENV=production
APP_KEY=base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
APP_DEBUG=false
APP_URL=https://your-domain.com

IS_IDP=true  # または false

# データベース設定
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 5. アプリケーションキーの生成

```bash
php artisan key:generate
```

### 6. キャッシュのクリア

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### 7. 設定のキャッシュ（本番環境）

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## エラーが発生した場合の確認事項

1. **ログの確認**
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. **権限の確認**
   ```bash
   ls -la storage/
   ls -la bootstrap/cache/
   ```

3. **.envファイルの確認**
   ```bash
   cat .env
   ```

4. **PHPバージョンの確認**
   ```bash
   php -v  # PHP 8.1以上が必要
   ```

5. **Composer依存関係の確認**
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

## 桜レンタルサーバーでの注意事項

1. **ドキュメントルートの設定**
   - `public`ディレクトリをドキュメントルートに設定
   - または、`.htaccess`で`public`ディレクトリにリダイレクト

2. **PHPバージョン**
   - PHP 8.1以上が必要
   - サーバー管理画面でPHPバージョンを確認

3. **拡張機能**
   - `openssl`, `pdo`, `mbstring`, `xml`, `ctype`, `json` が必要

4. **メモリ制限**
   - `memory_limit`を256M以上に設定（可能であれば）

