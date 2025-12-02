<?php

namespace App\Services\Idp;

use App\Helpers\OidcHelper;

class OidcService
{
    public function getDiscoveryDocument(): array
    {
        $issuer = config('oidc.idp.issuer');

        return [
            'issuer' => $issuer,
            'authorization_endpoint' => config('oidc.idp.authorization_endpoint'),
            'token_endpoint' => config('oidc.idp.token_endpoint'),
            'userinfo_endpoint' => config('oidc.idp.userinfo_endpoint'),
            'end_session_endpoint' => config('oidc.idp.end_session_endpoint'),
            'jwks_uri' => config('oidc.idp.jwks_uri'),
            'response_types_supported' => ['code'],
            'subject_types_supported' => ['public'],
            'id_token_signing_alg_values_supported' => ['RS256'],
            'scopes_supported' => ['openid', 'profile', 'email'],
            'token_endpoint_auth_methods_supported' => ['client_secret_post', 'client_secret_basic'],
            'claims_supported' => ['sub', 'name', 'email', 'email_verified'],
        ];
    }

    public function getJwks(): array
    {
        $publicKeyPath = storage_path('oauth-public.key');
        
        if (!file_exists($publicKeyPath)) {
            throw new \RuntimeException('OAuth public key not found. Please run: php artisan passport:keys');
        }

        $publicKey = file_get_contents($publicKeyPath);
        if ($publicKey === false) {
            throw new \RuntimeException('Failed to read OAuth public key');
        }

        $publicKeyResource = openssl_pkey_get_public($publicKey);
        if ($publicKeyResource === false) {
            throw new \RuntimeException('Invalid OAuth public key format');
        }

        $details = openssl_pkey_get_details($publicKeyResource);
        if ($details === false || !isset($details['rsa'])) {
            throw new \RuntimeException('Failed to get RSA key details');
        }

        $modulus = OidcHelper::base64url_encode($details['rsa']['n']);
        $exponent = OidcHelper::base64url_encode($details['rsa']['e']);

        return [
            'keys' => [
                [
                    'kty' => 'RSA',
                    'use' => 'sig',
                    'kid' => '1',
                    'n' => $modulus,
                    'e' => $exponent,
                    'alg' => 'RS256',
                ],
            ],
        ];
    }
}

