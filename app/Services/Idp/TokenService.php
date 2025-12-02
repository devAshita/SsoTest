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
            ->firstOrFail();

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

        if ($session && $session->code_challenge) {
            $codeVerifier = $request->code_verifier;
            $codeChallenge = OidcHelper::base64url_encode(hash('sha256', $codeVerifier, true));
            
            if ($codeChallenge !== $session->code_challenge) {
                abort(400, 'Invalid code_verifier');
            }
        }

        $user = User::findOrFail($authCode->user_id);
        $scopes = explode(' ', $authCode->scopes);

        $accessToken = $user->createToken('access_token', $scopes);
        $refreshToken = $accessToken->token->refreshToken;

        $idToken = $this->createIdToken($user, $client, $scopes, $session->nonce ?? null);

        DB::table('oauth_auth_codes')
            ->where('id', $authCode->id)
            ->update(['revoked' => true]);

        return response()->json([
            'access_token' => $accessToken->accessToken,
            'token_type' => 'Bearer',
            'expires_in' => config('oidc.idp.access_token_lifetime'),
            'refresh_token' => $refreshToken->id,
            'id_token' => $idToken,
            'scope' => $authCode->scopes,
        ]);
    }

    private function createIdToken(User $user, Client $client, array $scopes, ?string $nonce): string
    {
        $config = Configuration::forAsymmetricSigner(
            new Sha256(),
            InMemory::file(storage_path('oauth-private.key')),
            InMemory::file(storage_path('oauth-public.key'))
        );

        $now = new \DateTimeImmutable();
        $expiresAt = $now->modify('+' . config('oidc.idp.id_token_lifetime') . ' seconds');

        $builder = $config->builder()
            ->issuedBy(config('oidc.idp.issuer'))
            ->permittedFor((string) $client->id)
            ->identifiedBy(Str::random(40))
            ->issuedAt($now)
            ->expiresAt($expiresAt)
            ->withClaim('sub', (string) $user->id)
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

