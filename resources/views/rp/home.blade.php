<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SSO Test RP</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
        }
        h1 {
            color: #333;
            margin-bottom: 30px;
            text-align: center;
        }
        .user-info {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .user-info h2 {
            margin-top: 0;
            color: #667eea;
        }
        .info-item {
            margin: 10px 0;
        }
        .info-label {
            font-weight: 600;
            color: #666;
        }
        .info-value {
            color: #333;
            margin-left: 10px;
        }
        .buttons {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }
        button, a {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            display: inline-block;
            transition: all 0.3s;
        }
        .btn-login {
            background: #667eea;
            color: white;
        }
        .btn-login:hover {
            background: #5568d3;
        }
        .btn-logout {
            background: #e74c3c;
            color: white;
        }
        .btn-logout:hover {
            background: #c0392b;
        }
        .not-logged-in {
            text-align: center;
            color: #666;
            padding: 40px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>SSO Test RP</h1>
        
        @if($user)
            <div class="user-info">
                <h2>ユーザー情報</h2>
                <div class="info-item">
                    <span class="info-label">ID:</span>
                    <span class="info-value">{{ $user['sub'] }}</span>
                </div>
                @if(isset($user['name']))
                <div class="info-item">
                    <span class="info-label">名前:</span>
                    <span class="info-value">{{ $user['name'] }}</span>
                </div>
                @endif
                @if(isset($user['email']))
                <div class="info-item">
                    <span class="info-label">メールアドレス:</span>
                    <span class="info-value">{{ $user['email'] }}</span>
                </div>
                @endif
            </div>

            <div class="buttons">
                <form method="POST" action="{{ route('rp.logout') }}" style="flex: 1;">
                    @csrf
                    <button type="submit" class="btn-logout">ログアウト</button>
                </form>
            </div>
        @else
            <div class="not-logged-in">
                <p>ログインしていません</p>
                <a href="{{ route('rp.login') }}" class="btn-login">ログイン</a>
            </div>
        @endif
    </div>
</body>
</html>

