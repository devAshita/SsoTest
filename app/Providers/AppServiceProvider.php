<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(\App\Providers\RouteServiceProvider::class);
    }

    public function boot(): void
    {
        if (config('oidc.is_idp')) {
            Passport::tokensExpireIn(now()->addSeconds(config('oidc.idp.access_token_lifetime')));
            Passport::refreshTokensExpireIn(now()->addSeconds(config('oidc.idp.refresh_token_lifetime')));
            
            // OIDCスコープを定義
            Passport::tokensCan([
                'openid' => 'OpenID Connect認証',
                'profile' => 'プロフィール情報へのアクセス',
                'email' => 'メールアドレスへのアクセス',
            ]);
        }
    }
}

