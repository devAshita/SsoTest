# SSO導入計画書（OpenID Connect版）

## 1. プロジェクト概要

### 1.1 目的
SaaS業務アプリケーション（アプリA）をIDP（Identity Provider）として、他のアプリケーションへの認証を一元管理するSSO（Single Sign-On）システムを構築する。

### 1.2 プロジェクト名
**SsoTest**

### 1.3 構成
- **IDP（Identity Provider / Authorization Server）**: 認証を提供する側のアプリケーション（アプリA）
- **RP（Relying Party / Client）**: 認証を受ける側のアプリケーション

### 1.4 デプロイ先
桜レンタルサーバー
- **SsoTestIdp**: IDPアプリケーション
- **SsoTestRp**: RPアプリケーション

## 2. アーキテクチャ設計

### 2.1 基本構成
```
┌─────────────────┐         ┌─────────────────┐
│   SsoTestIdp    │         │   SsoTestRp     │
│   (IDP Server)  │◄───────►│   (RP Client)   │
│                 │  OIDC   │                 │
│  env.IsIdp=true │         │ env.IsIdp=false │
└─────────────────┘         └─────────────────┘
```

### 2.2 認証フロー（Authorization Code Flow + PKCE）
1. ユーザーがRPアプリケーションにアクセス
2. RPがIDPの認証エンドポイント（`/oauth/authorize`）にリダイレクト
   - `client_id`、`redirect_uri`、`code_challenge`（PKCE）、`state`（CSRF対策）を含む
3. IDPがユーザー認証画面を表示
4. ユーザーが認証情報を入力
5. IDPが認証成功後、認証コードをRPの`redirect_uri`に返却
6. RPが認証コードをIDPのトークンエンドポイント（`/oauth/token`）に送信
   - `code`、`code_verifier`（PKCE）、`client_id`、`client_secret`を含む
7. IDPがID Token、Access Token、Refresh Tokenを発行
8. RPがID Tokenを検証し、ユーザーセッションを確立

### 2.3 単一ソースコード設計
- 1つのLaravelプロジェクトでIDPとRPの両方の機能を実装
- `env('IS_IDP')` の値（`true`/`false`）により動作を切り替え
- 共通の認証ロジックと、IDP/RP固有の処理を分離

## 3. 技術スタック

### 3.1 フレームワーク
- **Laravel**: 10.x または 11.x
- **PHP**: 8.1以上

### 3.2 SSO実装方式
**OpenID Connect (OIDC)** を採用
- OAuth 2.0の上に構築された認証プロトコル
- RESTful APIベースで実装が容易
- モダンなWebアプリケーションに適している
- JWT（JSON Web Token）を使用した軽量な実装
- PKCE（Proof Key for Code Exchange）によるセキュリティ強化

### 3.3 使用パッケージ

#### 3.3.1 IDP側
- **laravel/passport**: OAuth 2.0サーバー実装（Laravel公式）
  - OpenID Connectの基盤となるOAuth 2.0を実装
  - トークン発行・管理機能
- **league/oauth2-server**: OAuth 2.0サーバー実装（Passportの依存関係）

#### 3.3.2 RP側
- **guzzlehttp/guzzle**: HTTPクライアント（IDPとの通信用）
- **firebase/php-jwt**: JWT検証ライブラリ（ID Token検証用）

#### 3.3.3 共通
- **laravel/sanctum**: API認証（オプション、内部API用）

### 3.4 データベース
- **MySQL** または **PostgreSQL**
- ユーザー情報、OAuth 2.0クライアント情報、トークン管理、セッション管理の保存

### 3.5 その他
- **Redis**: セッション管理・トークンキャッシュ（オプション）
- **HTTPS**: 必須（OAuth 2.0/OIDCのセキュリティ要件）

## 4. 機能要件

### 4.1 IDP（Identity Provider）機能

#### 4.1.1 認証機能
- [ ] ユーザーログイン画面
- [ ] ユーザー認証処理（メール/パスワード）
- [ ] セッション管理
- [ ] ログアウト機能
- [ ] 同意画面（Consent Screen）

#### 4.1.2 OpenID Connect機能
- [ ] OpenID Connect Discovery（`/.well-known/openid-configuration`）
- [ ] JWKS（JSON Web Key Set）エンドポイント（`/.well-known/jwks.json`）
- [ ] Authorizationエンドポイント（`/oauth/authorize`）
- [ ] Tokenエンドポイント（`/oauth/token`）
- [ ] UserInfoエンドポイント（`/oauth/userinfo`）
- [ ] End Sessionエンドポイント（`/oauth/logout`）
- [ ] ID Token発行（JWT形式）
- [ ] Access Token発行
- [ ] Refresh Token発行

#### 4.1.3 OAuth 2.0機能
- [ ] Authorization Code Flow実装
- [ ] PKCE（Proof Key for Code Exchange）サポート
- [ ] トークン検証機能
- [ ] トークンリフレッシュ機能
- [ ] トークン無効化機能

#### 4.1.4 管理機能
- [ ] RP（OAuth Client）の登録・管理
  - Client ID、Client Secret生成
  - Redirect URI登録
  - スコープ設定
- [ ] ユーザー管理
- [ ] トークン管理・監視
- [ ] ログ管理

### 4.2 RP（Relying Party）機能

#### 4.2.1 OpenID Connect機能
- [ ] IDPのOpenID Connect Discovery取得
- [ ] IDPのJWKS取得・キャッシュ
- [ ] Authorizationリクエスト生成・送信
- [ ] Authorization Code受信
- [ ] Tokenリクエスト送信
- [ ] ID Token受信・検証
  - 署名検証（JWKS使用）
  - 発行者（iss）検証
  - 対象者（aud）検証
  - 有効期限（exp）検証
  - Nonce検証
- [ ] Access Token受信・保存
- [ ] Refresh Token受信・保存
- [ ] UserInfoエンドポイント呼び出し（オプション）

#### 4.2.2 OAuth 2.0機能
- [ ] Authorization Code Flow実装
- [ ] PKCE実装（code_challenge生成、code_verifier検証）
- [ ] StateパラメータによるCSRF対策
- [ ] トークンリフレッシュ処理

#### 4.2.3 セッション管理
- [ ] SSOセッション確立
- [ ] セッション有効期限管理
- [ ] セッションタイムアウト処理
- [ ] ログアウト処理（IDPへのログアウトリクエスト含む）

#### 4.2.4 アプリケーション機能
- [ ] 認証済みユーザーのみアクセス可能なページ
- [ ] ユーザー情報表示（ID Tokenから取得）
- [ ] ログアウト機能

### 4.3 共通機能

#### 4.3.1 設定管理
- [ ] 環境変数によるIDP/RP切り替え
- [ ] 設定ファイルの管理
- [ ] クライアント設定の管理

#### 4.3.2 セキュリティ
- [ ] HTTPS強制
- [ ] CSRF対策（Stateパラメータ）
- [ ] XSS対策
- [ ] JWT署名検証
- [ ] PKCEによる認証コード保護
- [ ] リプレイ攻撃対策（Nonce、タイムスタンプ）
- [ ] トークンの安全な保存

#### 4.3.3 ログ・監査
- [ ] 認証ログ
- [ ] OAuth 2.0リクエスト/レスポンスログ
- [ ] トークン発行ログ
- [ ] エラーログ

## 5. 実装計画

### 5.1 フェーズ1: プロジェクトセットアップ
- [ ] Laravelプロジェクト作成
- [ ] Laravel Passportインストール・設定（IDP用）
- [ ] 必要なパッケージのインストール
- [ ] 環境設定ファイルの作成
- [ ] データベース設計・マイグレーション作成

### 5.2 フェーズ2: IDP機能実装
- [ ] ユーザー認証機能実装
- [ ] Laravel Passport設定・カスタマイズ
- [ ] OpenID Connect Discoveryエンドポイント実装
- [ ] JWKSエンドポイント実装
- [ ] Authorizationエンドポイント実装
- [ ] Tokenエンドポイント実装（Passport標準機能を拡張）
- [ ] UserInfoエンドポイント実装
- [ ] End Sessionエンドポイント実装
- [ ] ID Tokenカスタマイズ（カスタムクレーム追加）
- [ ] 同意画面実装

### 5.3 フェーズ3: RP機能実装
- [ ] IDP Discovery取得機能
- [ ] JWKS取得・キャッシュ機能
- [ ] Authorizationリクエスト生成・送信機能
- [ ] PKCE実装（code_challenge/verifier生成）
- [ ] Authorization Code受信・処理機能
- [ ] Tokenリクエスト送信機能
- [ ] ID Token検証機能
- [ ] セッション確立機能
- [ ] トークンリフレッシュ機能
- [ ] ログアウト処理機能

### 5.4 フェーズ4: 統合テスト
- [ ] IDPとRPの連携テスト
- [ ] Authorization Code Flowのエンドツーエンドテスト
- [ ] PKCE動作テスト
- [ ] ID Token検証テスト
- [ ] トークンリフレッシュテスト
- [ ] ログアウトフローテスト
- [ ] エラーハンドリングテスト
- [ ] セキュリティテスト
- [ ] パフォーマンステスト

### 5.5 フェーズ5: デプロイ準備
- [ ] 桜レンタルサーバー環境設定
- [ ] 環境変数の設定
- [ ] OAuth 2.0クライアント設定
- [ ] デプロイスクリプト作成

## 6. 環境設定

### 6.1 環境変数設計

#### 6.1.1 共通設定
```env
APP_NAME=SsoTest
APP_ENV=production
APP_DEBUG=false
APP_URL=https://sso-test-idp.example.com

# IDP/RP切り替え
IS_IDP=true  # IDPの場合はtrue、RPの場合はfalse

# データベース設定
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sso_test
DB_USERNAME=root
DB_PASSWORD=

# セッション設定
SESSION_DRIVER=file
SESSION_LIFETIME=120
```

#### 6.1.2 IDP設定
```env
# IDP固有設定
OIDC_ISSUER=https://sso-test-idp.example.com
OIDC_AUTHORIZATION_ENDPOINT=https://sso-test-idp.example.com/oauth/authorize
OIDC_TOKEN_ENDPOINT=https://sso-test-idp.example.com/oauth/token
OIDC_USERINFO_ENDPOINT=https://sso-test-idp.example.com/oauth/userinfo
OIDC_END_SESSION_ENDPOINT=https://sso-test-idp.example.com/oauth/logout
OIDC_JWKS_URI=https://sso-test-idp.example.com/.well-known/jwks.json

# Passport設定
PASSPORT_CLIENT_ID=
PASSPORT_CLIENT_SECRET=
PASSPORT_PRIVATE_KEY=
PASSPORT_PUBLIC_KEY=

# ID Token設定
OIDC_ID_TOKEN_LIFETIME=3600  # 1時間（秒）
OIDC_ACCESS_TOKEN_LIFETIME=3600  # 1時間（秒）
OIDC_REFRESH_TOKEN_LIFETIME=2592000  # 30日（秒）
```

#### 6.1.3 RP設定
```env
# RP固有設定
OIDC_IDP_ISSUER=https://sso-test-idp.example.com
OIDC_IDP_DISCOVERY_URL=https://sso-test-idp.example.com/.well-known/openid-configuration
OIDC_IDP_AUTHORIZATION_ENDPOINT=https://sso-test-idp.example.com/oauth/authorize
OIDC_IDP_TOKEN_ENDPOINT=https://sso-test-idp.example.com/oauth/token
OIDC_IDP_USERINFO_ENDPOINT=https://sso-test-idp.example.com/oauth/userinfo
OIDC_IDP_END_SESSION_ENDPOINT=https://sso-test-idp.example.com/oauth/logout
OIDC_IDP_JWKS_URI=https://sso-test-idp.example.com/.well-known/jwks.json

# OAuth 2.0 Client設定
OIDC_CLIENT_ID=your-client-id
OIDC_CLIENT_SECRET=your-client-secret
OIDC_REDIRECT_URI=https://sso-test-rp.example.com/oauth/callback

# PKCE設定
OIDC_USE_PKCE=true
OIDC_CODE_CHALLENGE_METHOD=S256
```

### 6.2 ディレクトリ構造
```
SsoTest/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Idp/
│   │   │   │   ├── OidcController.php
│   │   │   │   ├── AuthorizationController.php
│   │   │   │   ├── TokenController.php
│   │   │   │   ├── UserInfoController.php
│   │   │   │   └── AuthController.php
│   │   │   └── Rp/
│   │   │       ├── OidcController.php
│   │   │       ├── CallbackController.php
│   │   │       └── HomeController.php
│   │   └── Middleware/
│   │       ├── OidcAuth.php
│   │       └── VerifyIdToken.php
│   ├── Models/
│   │   ├── User.php
│   │   └── OAuthClient.php
│   └── Services/
│       ├── Idp/
│       │   ├── OidcService.php
│       │   ├── TokenService.php
│       │   └── UserInfoService.php
│       └── Rp/
│           ├── OidcService.php
│           ├── DiscoveryService.php
│           ├── JwtVerificationService.php
│           └── TokenService.php
├── config/
│   ├── oidc.php
│   ├── oidc-idp.php
│   └── oidc-rp.php
├── routes/
│   ├── web.php
│   ├── idp.php
│   └── rp.php
├── database/
│   ├── migrations/
│   └── seeds/
├── resources/
│   └── views/
│       ├── idp/
│       │   ├── login.blade.php
│       │   └── consent.blade.php
│       └── rp/
│           └── home.blade.php
└── .env
```

## 7. データベース設計

### 7.1 テーブル設計

#### 7.1.1 users（ユーザー）
```sql
- id (bigint, primary key)
- name (string)
- email (string, unique)
- password (string, hashed)
- email_verified_at (timestamp, nullable)
- created_at (timestamp)
- updated_at (timestamp)
```

#### 7.1.2 oauth_clients（OAuth 2.0クライアント管理）
```sql
- id (bigint, primary key)
- user_id (bigint, foreign key, nullable)
- name (string)
- secret (string, nullable)
- provider (string, nullable)
- redirect (text)
- personal_access_client (boolean)
- password_client (boolean)
- revoked (boolean)
- created_at (timestamp)
- updated_at (timestamp)
```
※ Laravel Passportが自動生成するテーブル

#### 7.1.3 oauth_access_tokens（Access Token管理）
```sql
- id (bigint, primary key)
- user_id (bigint, foreign key, nullable)
- client_id (bigint, foreign key)
- name (string, nullable)
- scopes (text, nullable)
- revoked (boolean)
- expires_at (timestamp, nullable)
- created_at (timestamp)
- updated_at (timestamp)
```
※ Laravel Passportが自動生成するテーブル

#### 7.1.4 oauth_refresh_tokens（Refresh Token管理）
```sql
- id (bigint, primary key)
- access_token_id (bigint, foreign key)
- revoked (boolean)
- expires_at (timestamp, nullable)
- created_at (timestamp)
- updated_at (timestamp)
```
※ Laravel Passportが自動生成するテーブル

#### 7.1.5 oauth_auth_codes（Authorization Code管理）
```sql
- id (bigint, primary key)
- user_id (bigint, foreign key)
- client_id (bigint, foreign key)
- scopes (text, nullable)
- revoked (boolean)
- expires_at (timestamp, nullable)
- created_at (timestamp)
- updated_at (timestamp)
```
※ Laravel Passportが自動生成するテーブル

#### 7.1.6 oidc_sessions（OIDCセッション管理）
```sql
- id (bigint, primary key)
- user_id (bigint, foreign key)
- session_id (string, unique)
- client_id (bigint, foreign key)
- id_token (text, nullable)  # 発行したID TokenのJTIを保存
- nonce (string, nullable)
- state (string, nullable)
- code_challenge (string, nullable)  # PKCE用
- code_challenge_method (string, nullable)  # S256
- expires_at (timestamp)
- created_at (timestamp)
- updated_at (timestamp)
```

## 8. セキュリティ考慮事項

### 8.1 OpenID Connectセキュリティ
- [ ] ID Token署名の検証（RS256推奨）
- [ ] ID Tokenの有効期限検証
- [ ] Nonce検証によるリプレイ攻撃対策
- [ ] StateパラメータによるCSRF対策
- [ ] PKCEによる認証コード保護
- [ ] HTTPS通信の強制
- [ ] リダイレクトURIの検証
- [ ] クライアント認証（Client Secret検証）

### 8.2 JWTセキュリティ
- [ ] 署名アルゴリズムの検証（RS256推奨、HS256は非推奨）
- [ ] 発行者（iss）の検証
- [ ] 対象者（aud）の検証
- [ ] 有効期限（exp）の検証
- [ ] 発行時刻（iat）の検証
- [ ] JWKSのキャッシュと更新

### 8.3 OAuth 2.0セキュリティ
- [ ] Authorization Codeの有効期限管理（10分以内推奨）
- [ ] トークンの安全な保存（暗号化）
- [ ] Refresh Tokenのローテーション
- [ ] トークン無効化機能
- [ ] スコープの適切な管理

### 8.4 アプリケーションセキュリティ
- [ ] SQLインジェクション対策（Eloquent ORM使用）
- [ ] XSS対策（Bladeテンプレートの自動エスケープ）
- [ ] CSRF対策（Laravel標準機能 + Stateパラメータ）
- [ ] セッションハイジャック対策
- [ ] パスワードハッシュ化（bcrypt）
- [ ] レート制限（認証エンドポイント）

### 8.5 証明書・鍵管理
- [ ] RSA鍵ペアの生成（Laravel Passportが自動生成）
- [ ] 秘密鍵の安全な保管（環境変数または暗号化ストレージ）
- [ ] 公開鍵のJWKS形式での公開
- [ ] 鍵のローテーション計画

## 9. デプロイ計画

### 9.1 桜レンタルサーバー環境設定

#### 9.1.1 SsoTestIdp（IDP）
- ドメイン: `sso-test-idp.example.com`（実際のドメインに置き換え）
- データベース: `sso_test_idp`
- `IS_IDP=true` を設定
- Laravel Passportの鍵生成（`php artisan passport:keys`）

#### 9.1.2 SsoTestRp（RP）
- ドメイン: `sso-test-rp.example.com`（実際のドメインに置き換え）
- データベース: `sso_test_rp`
- `IS_IDP=false` を設定
- IDPで発行されたClient ID/Secretを設定

### 9.2 デプロイ手順
1. ソースコードをGitリポジトリにプッシュ
2. 桜レンタルサーバーにSSH接続
3. 各アプリケーション用ディレクトリにクローン
4. 依存関係のインストール（`composer install`）
5. 環境変数ファイルの設定（`.env`）
6. データベースマイグレーション実行
7. Laravel Passport鍵生成（IDPのみ: `php artisan passport:keys`）
8. OAuth 2.0クライアント作成（IDPで実行: `php artisan passport:client`）
8. ストレージ権限の設定
9. Webサーバー設定（Apache/Nginx）
10. SSL証明書の設定

### 9.3 デプロイ後確認事項
- [ ] IDPのOpenID Connect Discoveryが正常に公開されているか
- [ ] IDPのJWKSが正常に公開されているか
- [ ] RPがIDPのDiscoveryを取得できるか
- [ ] Authorization Code Flowが正常に動作するか
- [ ] ID Tokenが正常に発行・検証されるか
- [ ] ログアウト機能が正常に動作するか
- [ ] HTTPSが正常に動作しているか
- [ ] エラーログに問題がないか

## 10. テスト計画

### 10.1 単体テスト
- [ ] ユーザー認証機能のテスト
- [ ] ID Token生成のテスト
- [ ] ID Token検証のテスト
- [ ] PKCE実装のテスト
- [ ] JWKS生成のテスト

### 10.2 統合テスト
- [ ] IDPとRPの連携テスト
- [ ] エンドツーエンドの認証フローテスト
- [ ] トークンリフレッシュフローテスト
- [ ] ログアウトフローテスト

### 10.3 セキュリティテスト
- [ ] 不正なAuthorizationリクエストの検証
- [ ] 署名なしID Tokenの拒否テスト
- [ ] 有効期限切れID Tokenの拒否テスト
- [ ] Stateパラメータ検証テスト
- [ ] Nonce検証テスト
- [ ] リダイレクトURI検証テスト
- [ ] セッションタイムアウトテスト

## 11. 運用・保守

### 11.1 ログ監視
- 認証ログの定期確認
- OAuth 2.0リクエスト/レスポンスログの確認
- トークン発行ログの監視
- エラーログの監視

### 11.2 トークン管理
- アクティブなトークンの監視
- トークンの有効期限管理
- トークン無効化プロセスの確立

### 11.3 鍵管理
- Passport鍵の有効期限監視（通常は無期限）
- 鍵ローテーション計画の確立

### 11.4 バックアップ
- データベースの定期バックアップ
- OAuth 2.0クライアント設定のバックアップ

## 12. 今後の拡張予定

### 12.1 機能拡張
- [ ] 多要素認証（MFA）の追加
- [ ] シングルログアウト（Single Logout）の完全実装
- [ ] ユーザー属性のカスタムクレーム追加
- [ ] 複数RPへの同時認証
- [ ] 認証方法の拡張（ソーシャルログイン連携）

### 12.2 パフォーマンス改善
- [ ] Redisによるセッション管理
- [ ] JWKSのキャッシュ
- [ ] Discovery情報のキャッシュ
- [ ] レスポンス時間の最適化

### 12.3 セキュリティ強化
- [ ] トークン暗号化の実装
- [ ] より厳格なスコープ管理
- [ ] 認証イベントの監査ログ強化

## 13. OpenID Connect仕様詳細

### 13.1 エンドポイント一覧

#### 13.1.1 Discovery
- **URL**: `/.well-known/openid-configuration`
- **Method**: GET
- **説明**: OpenID Connect設定情報をJSON形式で返却

#### 13.1.2 JWKS
- **URL**: `/.well-known/jwks.json`
- **Method**: GET
- **説明**: ID Token検証用の公開鍵セットを返却

#### 13.1.3 Authorization
- **URL**: `/oauth/authorize`
- **Method**: GET
- **説明**: 認証リクエストを受け付け、認証画面を表示

#### 13.1.4 Token
- **URL**: `/oauth/token`
- **Method**: POST
- **説明**: Authorization CodeをAccess Token/ID Tokenに交換

#### 13.1.5 UserInfo
- **URL**: `/oauth/userinfo`
- **Method**: GET
- **説明**: Access Tokenを使用してユーザー情報を取得

#### 13.1.6 End Session
- **URL**: `/oauth/logout`
- **Method**: GET/POST
- **説明**: セッション終了処理

### 13.2 ID Tokenクレーム
標準的なID Tokenに含まれるクレーム：
- `iss`: 発行者（Issuer）
- `sub`: サブジェクト（ユーザーID）
- `aud`: 対象者（Client ID）
- `exp`: 有効期限
- `iat`: 発行時刻
- `nonce`: リプレイ攻撃対策用
- `auth_time`: 認証時刻
- `email`: メールアドレス（オプション）
- `name`: ユーザー名（オプション）

## 14. 参考資料

### 14.1 OpenID Connect仕様
- OpenID Connect Core 1.0: https://openid.net/specs/openid-connect-core-1_0.html
- OpenID Connect Discovery 1.0: https://openid.net/specs/openid-connect-discovery-1_0.html
- OAuth 2.0 Authorization Code Flow: https://oauth.net/2/grant-types/authorization-code/
- PKCE: https://oauth.net/2/pkce/

### 14.2 Laravelパッケージ
- Laravel Passport: https://laravel.com/docs/passport
- Laravel Passport GitHub: https://github.com/laravel/passport

### 14.3 JWT
- JWT.io: https://jwt.io/
- JSON Web Token (RFC 7519): https://tools.ietf.org/html/rfc7519
- JSON Web Key (RFC 7517): https://tools.ietf.org/html/rfc7517

### 14.4 その他
- Laravel公式ドキュメント: https://laravel.com/docs
- OpenID Connect Debugger: https://oidcdebugger.com/

---

**作成日**: 2024年
**最終更新日**: 2024年
**作成者**: SSO導入プロジェクトチーム
**プロトコル**: OpenID Connect 1.0
