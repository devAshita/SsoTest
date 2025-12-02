<?php

namespace App\Services\Idp;

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
        $publicKey = file_get_contents(storage_path('oauth-public.key'));
        $publicKeyResource = openssl_pkey_get_public($publicKey);
        $details = openssl_pkey_get_details($publicKeyResource);

        $modulus = base64url_encode($details['rsa']['n']);
        $exponent = base64url_encode($details['rsa']['e']);

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

function base64url_encode($data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

