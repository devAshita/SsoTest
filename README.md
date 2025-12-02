# SSO Test - OpenID Connect実装

Laravelを使用したOpenID Connect (OIDC) ベースのSSOシステムです。

## 機能

- **IDP (Identity Provider)**: 認証を提供する側のアプリケーション
- **RP (Relying Party)**: 認証を受ける側のアプリケーション
- 1つのソースコードでIDP/RPの両方を実装
- `IS_IDP`環境変数で動作を切り替え

## セットアップ

### 1. 依存関係のインストール

```bash
composer install
```

### 2. 環境設定

`.env`ファイルを作成し、設定を行います。

#### IDP用設定例

```env
APP_NAME=SsoTest
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

IS_IDP=true

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sso_test_idp
DB_USERNAME=root
DB_PASSWORD=
```

#### RP用設定例

```env
APP_NAME=SsoTest
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8001

IS_IDP=false

OIDC_IDP_ISSUER=http://localhost:8000
OIDC_IDP_DISCOVERY_URL=http://localhost:8000/.well-known/openid-configuration
OIDC_IDP_AUTHORIZATION_ENDPOINT=http://localhost:8000/oauth/authorize
OIDC_IDP_TOKEN_ENDPOINT=http://localhost:8000/oauth/token
OIDC_IDP_USERINFO_ENDPOINT=http://localhost:8000/oauth/userinfo
OIDC_IDP_END_SESSION_ENDPOINT=http://localhost:8000/oauth/logout
OIDC_IDP_JWKS_URI=http://localhost:8000/.well-known/jwks.json
# 以下はIDPでOAuth 2.0クライアント作成後に取得した値を設定してください
OIDC_CLIENT_ID=your-client-id  # ステップ6で取得したClient IDに置き換え
OIDC_CLIENT_SECRET=your-client-secret  # ステップ6で取得したClient Secretに置き換え
OIDC_REDIRECT_URI=http://localhost:8001/oauth/callback
OIDC_USE_PKCE=true
OIDC_CODE_CHALLENGE_METHOD=S256

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sso_test_rp
DB_USERNAME=root
DB_PASSWORD=
```

### 3. アプリケーションキーの生成

```bash
php artisan key:generate
```

### 4. Passportのマイグレーションファイルの公開（開発環境のみ）

**注意**: この手順は開発環境で1回だけ実行し、生成されたマイグレーションファイルをGitにコミットしてください。

```bash
php artisan vendor:publish --tag=passport-migrations
```

これにより、以下のマイグレーションファイルが`database/migrations`に生成されます：
- `2016_06_01_000001_create_oauth_auth_codes_table.php`
- `2016_06_01_000002_create_oauth_access_tokens_table.php`
- `2016_06_01_000003_create_oauth_refresh_tokens_table.php`
- `2016_06_01_000004_create_oauth_clients_table.php`
- `2016_06_01_000005_create_oauth_personal_access_clients_table.php`

これらのファイルをGitにコミットすれば、本番環境では`migrate`のみで済みます。

### 5. データベースマイグレーション

```bash
php artisan migrate
```

### 6. Passportのセットアップ（IDPのみ）

```bash
php artisan passport:install
php artisan passport:keys
```

### 7. OAuth 2.0クライアントの作成（IDPで実行）

IDPサーバーで以下のコマンドを実行します：

```bash
php artisan passport:client
```

対話形式で以下の情報を入力します：
- **名前**: 任意の名前（例: "RP Client"）
- **リダイレクトURI**: RPのコールバックURL（例: `http://localhost:8001/oauth/callback`）

実行後、以下のような出力が表示されます：

```
Client ID: 1
Client secret: xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

**重要**: このClient IDとClient Secretをコピーして、RPの`.env`ファイルに設定してください：

```env
OIDC_CLIENT_ID=1
OIDC_CLIENT_SECRET=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

**注意**: Client Secretは一度しか表示されないため、必ずコピーして安全に保管してください。

### 8. テストユーザーの作成

```bash
php artisan tinker
```

```php
App\Models\User::create([
    'name' => 'Test User',
    'email' => 'test@example.com',
    'password' => bcrypt('password'),
]);
```

## 実行方法

### IDPサーバー

```bash
php artisan serve --port=8000
```

### RPサーバー

```bash
php artisan serve --port=8001
```

## エンドポイント

### IDP

- `/.well-known/openid-configuration` - OpenID Connect Discovery
- `/.well-known/jwks.json` - JWKS (JSON Web Key Set)
- `/oauth/authorize` - 認証エンドポイント
- `/oauth/token` - トークンエンドポイント
- `/oauth/userinfo` - UserInfoエンドポイント
- `/oauth/logout` - ログアウトエンドポイント

### RP

- `/` - ホームページ
- `/login` - ログイン（IDPにリダイレクト）
- `/oauth/callback` - 認証コールバック
- `/logout` - ログアウト

## テスト手順

1. IDPサーバーを起動（ポート8000）
2. RPサーバーを起動（ポート8001）
3. RPのホームページ（http://localhost:8001）にアクセス
4. 「ログイン」ボタンをクリック
5. IDPのログイン画面で認証情報を入力
6. 同意画面で「承認」をクリック
7. RPにリダイレクトされ、ユーザー情報が表示される

## デプロイ

### 開発環境での準備

1. Passportのマイグレーションファイルを公開（1回のみ）
   ```bash
   php artisan vendor:publish --tag=passport-migrations
   ```

2. マイグレーションファイルをGitにコミット
   ```bash
   git add database/migrations/
   git commit -m "Add Passport migrations"
   ```

### 本番環境でのデプロイ手順

1. ソースコードをデプロイ（Gitからクローンまたはプル）
2. 依存関係のインストール
   ```bash
   composer install --no-dev --optimize-autoloader
   ```
3. 環境変数の設定（`.env`ファイル）
4. アプリケーションキーの生成
   ```bash
   php artisan key:generate
   ```
5. **データベースマイグレーションの実行**
   ```bash
   php artisan migrate --force
   ```
   **注意**: 本番環境では`vendor:publish`は不要です。マイグレーションファイルは既にGitに含まれています。
6. Passportのセットアップ（IDPのみ）
   ```bash
   php artisan passport:install
   php artisan passport:keys
   ```
7. OAuth 2.0クライアントの作成（IDPで実行）
   ```bash
   php artisan passport:client
   ```
8. キャッシュの最適化
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

桜レンタルサーバーへのデプロイ手順の詳細は計画書を参照してください。

