# デプロイチェックリスト

## RP側の`.env`設定確認

### 必須設定項目

RP側の`.env`ファイルで、以下の設定が**実際のIDPサーバーのURL**になっているか確認してください：

```env
# IDPサーバーのURL（localhostではなく実際のドメイン）
OIDC_IDP_ISSUER=https://your-idp-domain.com
OIDC_IDP_DISCOVERY_URL=https://your-idp-domain.com/.well-known/openid-configuration
OIDC_IDP_AUTHORIZATION_ENDPOINT=https://your-idp-domain.com/oauth/authorize
OIDC_IDP_TOKEN_ENDPOINT=https://your-idp-domain.com/oauth/token
OIDC_IDP_USERINFO_ENDPOINT=https://your-idp-domain.com/oauth/userinfo
OIDC_IDP_END_SESSION_ENDPOINT=https://your-idp-domain.com/oauth/logout
OIDC_IDP_JWKS_URI=https://your-idp-domain.com/.well-known/jwks.json

# RP側のURL（実際のドメイン）
OIDC_REDIRECT_URI=https://your-rp-domain.com/oauth/callback
```

### よくある間違い

❌ **間違い**: `http://localhost`を使用している
```env
OIDC_IDP_TOKEN_ENDPOINT=http://localhost/oauth/token  # これは動作しません
```

✅ **正しい**: 実際のドメインを使用する
```env
OIDC_IDP_TOKEN_ENDPOINT=https://your-idp-domain.com/oauth/token
```

## IDP側の`.env`設定確認

IDP側の`.env`ファイルでも、`APP_URL`が実際のドメインになっているか確認：

```env
APP_URL=https://your-idp-domain.com
```

## 設定確認コマンド

サーバー側で以下を実行して設定を確認：

```bash
# RP側で実行
php artisan tinker
# その後：
# config('oidc.rp.idp_token_endpoint');
# config('oidc.rp.idp_discovery_url');
```

## Discoveryキャッシュのクリア

設定を変更した後は、Discoveryのキャッシュをクリアしてください：

```bash
php artisan cache:clear
```

## エラーが発生した場合の確認事項

1. **IDPサーバーが実際にアクセス可能か確認**
   ```bash
   curl https://your-idp-domain.com/.well-known/openid-configuration
   ```

2. **RP側の設定が正しいか確認**
   - `.env`ファイルの`OIDC_IDP_*`設定が実際のドメインになっているか
   - `localhost`や`127.0.0.1`が含まれていないか

3. **HTTPSが正しく設定されているか確認**
   - 本番環境ではHTTPSを使用する必要があります

