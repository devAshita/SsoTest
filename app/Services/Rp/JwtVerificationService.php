<?php

namespace App\Services\Rp;

use App\Helpers\OidcHelper;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;

class JwtVerificationService
{
    public function verify(string $idToken, ?string $nonce): array
    {
        $jwks = $this->getJwks();
        
        try {
            $decoded = JWT::decode($idToken, $jwks);
        } catch (\Exception $e) {
            throw new \Exception('Invalid token: ' . $e->getMessage());
        }

        if ($decoded->iss !== config('oidc.rp.idp_issuer')) {
            throw new \Exception('Invalid issuer');
        }

        if ($decoded->aud !== config('oidc.rp.client_id')) {
            throw new \Exception('Invalid audience');
        }

        if ($decoded->exp < time()) {
            throw new \Exception('Token expired');
        }

        if ($nonce && (!isset($decoded->nonce) || $decoded->nonce !== $nonce)) {
            throw new \Exception('Invalid nonce');
        }

        return [
            'sub' => $decoded->sub,
            'name' => $decoded->name ?? null,
            'email' => $decoded->email ?? null,
            'email_verified' => $decoded->email_verified ?? false,
        ];
    }

    private function getJwks(): Key
    {
        $jwks = Cache::remember('oidc_jwks', 3600, function () {
            $client = new Client();
            $response = $client->get(config('oidc.rp.idp_jwks_uri'));
            
            return json_decode($response->getBody()->getContents(), true);
        });

        // 最初のキーを使用（実際の実装ではkidに基づいて選択）
        $key = $jwks['keys'][0];
        $publicKey = $this->convertJwkToPem($key);
        
        return new Key($publicKey, 'RS256');
    }

    private function convertJwkToPem(array $jwk): string
    {
        $n = OidcHelper::base64url_decode($jwk['n']);
        $e = OidcHelper::base64url_decode($jwk['e']);

        $modulus = gmp_import($n);
        $exponent = gmp_import($e);

        $rsa = [
            'n' => gmp_export($modulus),
            'e' => gmp_export($exponent),
        ];

        $publicKeyResource = openssl_pkey_new([
            'rsa' => $rsa,
        ]);

        if (!$publicKeyResource) {
            throw new \Exception('Failed to create public key');
        }

        $publicKeyDetails = openssl_pkey_get_details($publicKeyResource);
        
        return $publicKeyDetails['key'];
    }
}

