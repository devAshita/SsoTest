<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>認証の承認 - SSO Test IDP</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }
        .consent-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
            width: 100%;
            max-width: 500px;
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }
        .client-name {
            text-align: center;
            font-size: 20px;
            font-weight: 600;
            color: #667eea;
            margin-bottom: 30px;
        }
        .scopes {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 30px;
        }
        .scope-item {
            padding: 10px 0;
            border-bottom: 1px solid #ddd;
        }
        .scope-item:last-child {
            border-bottom: none;
        }
        .scope-name {
            font-weight: 600;
            color: #333;
        }
        .scope-desc {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }
        .buttons {
            display: flex;
            gap: 10px;
        }
        button {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-approve {
            background: #667eea;
            color: white;
        }
        .btn-approve:hover {
            background: #5568d3;
        }
        .btn-deny {
            background: #e74c3c;
            color: white;
        }
        .btn-deny:hover {
            background: #c0392b;
        }
    </style>
</head>
<body>
    <div class="consent-container">
        <h1>認証の承認</h1>
        <div class="client-name">{{ $client->name }}</div>
        <p style="text-align: center; color: #666; margin-bottom: 20px;">
            このアプリケーションがあなたの情報にアクセスすることを承認しますか？
        </p>
        
        <div class="scopes">
            <h3 style="margin-top: 0; color: #333;">要求されている権限:</h3>
            @foreach($scopes as $scope)
                <div class="scope-item">
                    <div class="scope-name">
                        @if($scope === 'openid') OpenID Connect
                        @elseif($scope === 'profile') プロフィール情報
                        @elseif($scope === 'email') メールアドレス
                        @else {{ $scope }}
                        @endif
                    </div>
                    <div class="scope-desc">
                        @if($scope === 'openid') あなたの識別情報にアクセス
                        @elseif($scope === 'profile') 名前などのプロフィール情報にアクセス
                        @elseif($scope === 'email') メールアドレスにアクセス
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <form method="POST" action="{{ route('oauth.authorize') }}">
            @csrf
            @foreach($request as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach
            
            <div class="buttons">
                <button type="submit" class="btn-approve">承認</button>
                <a href="/" class="btn-deny" style="text-decoration: none; display: flex; align-items: center; justify-content: center;">拒否</a>
            </div>
        </form>
    </div>
</body>
</html>

