<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SSO Test IDP</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
        }
        h1 {
            color: #333;
            margin-bottom: 30px;
        }
        .info-box {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .info-box h2 {
            margin-top: 0;
            color: #667eea;
        }
        .endpoint {
            font-family: monospace;
            background: white;
            padding: 10px;
            border-radius: 3px;
            margin: 5px 0;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>SSO Test IDP</h1>
        
        <div class="info-box">
            <h2>OpenID Connect Discovery</h2>
            <div class="endpoint">{{ url('/.well-known/openid-configuration') }}</div>
        </div>

        <div class="info-box">
            <h2>JWKS</h2>
            <div class="endpoint">{{ url('/.well-known/jwks.json') }}</div>
        </div>

        <div class="info-box">
            <h2>認証エンドポイント</h2>
            <div class="endpoint">{{ url('/oauth/authorize') }}</div>
        </div>

        <div class="info-box">
            <h2>トークンエンドポイント</h2>
            <div class="endpoint">{{ url('/oauth/token') }}</div>
        </div>

        <div class="info-box">
            <h2>UserInfoエンドポイント</h2>
            <div class="endpoint">{{ url('/oauth/userinfo') }}</div>
        </div>
    </div>
</body>
</html>

