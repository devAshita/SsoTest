<?php

namespace App\Services\Idp;

use App\Helpers\OidcHelper;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Passport\Client;
use Laravel\Passport\TokenRepository;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;

class TokenService
{
    public function createAuthorizationCode(User $user, array $params): string
    {
        $code = Str::random(40);
        
        DB::table('oauth_auth_codes')->insert([
            'id' => $code,
            'user_id' => $user->id,
            'client_id' => $params['client_id'],
            'scopes' => $params['scope'],
            'revoked' => false,
            'expires_at' => now()->addMinutes(10),
        ]);

        $sessionId = session()->getId();
        
        // 既存のセッションをチェック
        $existingSession = DB::table('oidc_sessions')
            ->where('session_id', $sessionId)
            ->where('client_id', $params['client_id'])
            ->first();
        
        if ($existingSession) {
            // 既存のセッションを更新
            DB::table('oidc_sessions')
                ->where('id', $existingSession->id)
                ->update([
                    'user_id' => $user->id,
                    'nonce' => $params['nonce'] ?? null,
                    'state' => $params['state'],
                    'code_challenge' => $params['code_challenge'] ?? null,
                    'code_challenge_method' => $params['code_challenge_method'] ?? null,
                    'expires_at' => now()->addHours(1),
                    'updated_at' => now(),
                ]);
        } else {
            // 新規セッションを挿入
            DB::table('oidc_sessions')->insert([
                'user_id' => $user->id,
                'session_id' => $sessionId,
                'client_id' => $params['client_id'],
                'nonce' => $params['nonce'] ?? null,
                'state' => $params['state'],
                'code_challenge' => $params['code_challenge'] ?? null,
                'code_challenge_method' => $params['code_challenge_method'] ?? null,
                'expires_at' => now()->addHours(1),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $code;
    }

    public function issueToken(Request $request)
    {
        $request->validate([
            'grant_type' => 'required|in:authorization_code',
            'code' => 'required',
            'redirect_uri' => 'required|url',
            'client_id' => 'required',
            'client_secret' => 'required',
            'code_verifier' => 'required_with:code_challenge',
        ]);

        $client = Client::where('id', $request->client_id)
            ->where('secret', $request->client_secret)
            ->where('revoked', false)
            ->first();
        
        if (!$client) {
            abort(400, 'Invalid client credentials');
        }

        $authCode = DB::table('oauth_auth_codes')
            ->where('id', $request->code)
            ->where('client_id', $request->client_id)
            ->where('revoked', false)
            ->where('expires_at', '>', now())
            ->first();

        if (!$authCode) {
            abort(400, 'Invalid authorization code');
        }

        $session = DB::table('oidc_sessions')
            ->where('client_id', $request->client_id)
            ->where('state', $request->input('state'))
            ->first();

        // PKCE検証: セッションが存在し、code_challengeが設定されている場合
        if ($session && isset($session->code_challenge) && $session->code_challenge) {
            if (!$request->has('code_verifier')) {
                abort(400, 'code_verifier is required');
            }
            
            $codeVerifier = $request->code_verifier;
            $codeChallenge = OidcHelper::base64url_encode(hash('sha256', $codeVerifier, true));
            
            if ($codeChallenge !== $session->code_challenge) {
                abort(400, 'Invalid code_verifier');
            }
        }

        $user = User::findOrFail($authCode->user_id);
        $scopes = explode(' ', $authCode->scopes);

        $accessToken = $user->createToken('access_token', $scopes, $client);
        $refreshToken = null;
        
        // refreshTokenが存在する場合のみ取得
        if ($accessToken->token && isset($accessToken->token->refreshToken) && $accessToken->token->refreshToken) {
            $refreshToken = $accessToken->token->refreshToken;
        }

        $idToken = $this->createIdToken($user, $client, $scopes, $session->nonce ?? null);

        DB::table('oauth_auth_codes')
            ->where('id', $authCode->id)
            ->update(['revoked' => true]);

        $response = [
            'access_token' => $accessToken->accessToken,
            'token_type' => 'Bearer',
            'expires_in' => config('oidc.idp.access_token_lifetime', 3600),
            'id_token' => $idToken,
            'scope' => $authCode->scopes,
        ];

        // refresh_tokenが存在する場合のみ追加
        if ($refreshToken) {
            $response['refresh_token'] = $refreshToken->id;
        }

        return response()->json($response);
    }

    private function createIdToken(User $user, Client $client, array $scopes, ?string $nonce): string
    {
        $privateKeyPath = storage_path('oauth-private.key');
        $publicKeyPath = storage_path('oauth-public.key');

        if (!file_exists($privateKeyPath) || !file_exists($publicKeyPath)) {
            throw new \RuntimeException('OAuth keys not found. Please run: php artisan passport:keys');
        }

        $config = Configuration::forAsymmetricSigner(
            new Sha256(),
            InMemory::file($privateKeyPath),
            InMemory::file($publicKeyPath)
        );

        $now = new \DateTimeImmutable();
        $idTokenLifetime = config('oidc.idp.id_token_lifetime', 3600);
        $expiresAt = $now->modify('+' . $idTokenLifetime . ' seconds');
        
        $issuer = config('oidc.idp.issuer');
        if (!$issuer) {
            throw new \RuntimeException('OIDC issuer not configured');
        }

        $builder = $config->builder()
            ->issuedBy($issuer)
            ->permittedFor((string) $client->id)
            ->relatedTo((string) $user->id)  // 'sub' クレームを設定
            ->identifiedBy(Str::random(40))
            ->issuedAt($now)
            ->expiresAt($expiresAt)
            ->withClaim('auth_time', $now->getTimestamp());
        
        // nonceがnullでない場合のみ追加
        if ($nonce !== null) {
            $builder->withClaim('nonce', $nonce);
        }

        if (in_array('profile', $scopes)) {
            $builder->withClaim('name', $user->name);
        }

        if (in_array('email', $scopes)) {
            $builder->withClaim('email', $user->email);
            $builder->withClaim('email_verified', $user->email_verified_at !== null);
        }

        return $builder->getToken($config->signer(), $config->signingKey())->toString();
    }
}

