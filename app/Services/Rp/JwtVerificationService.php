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
        $jwksUri = config('oidc.rp.idp_jwks_uri');
        
        if (!$jwksUri) {
            throw new \RuntimeException('JWKS URI not configured');
        }

        $jwks = Cache::remember('oidc_jwks', 3600, function () use ($jwksUri) {
            $client = new Client();
            
            try {
                $response = $client->get($jwksUri);
            } catch (\GuzzleHttp\Exception\RequestException $e) {
                throw new \RuntimeException('Failed to fetch JWKS: ' . $e->getMessage(), 0, $e);
            }
            
            $body = $response->getBody()->getContents();
            $jwks = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Invalid JSON in JWKS: ' . json_last_error_msg());
            }

            return $jwks;
        });

        if (!isset($jwks['keys']) || !is_array($jwks['keys']) || empty($jwks['keys'])) {
            throw new \RuntimeException('No keys found in JWKS');
        }

        // 最初のキーを使用（実際の実装ではkidに基づいて選択）
        $key = $jwks['keys'][0];
        $publicKey = $this->convertJwkToPem($key);
        
        return new Key($publicKey, 'RS256');
    }

    private function convertJwkToPem(array $jwk): string
    {
        if (!isset($jwk['n']) || !isset($jwk['e'])) {
            throw new \RuntimeException('Invalid JWK: missing required fields (n, e)');
        }

        if ($jwk['kty'] !== 'RSA') {
            throw new \RuntimeException('Unsupported key type: ' . ($jwk['kty'] ?? 'unknown'));
        }

        try {
            $n = OidcHelper::base64url_decode($jwk['n']);
            $e = OidcHelper::base64url_decode($jwk['e']);
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to decode JWK: ' . $e->getMessage(), 0, $e);
        }

        $modulus = gmp_import($n);
        $exponent = gmp_import($e);

        if ($modulus === false || $exponent === false) {
            throw new \RuntimeException('Failed to import RSA parameters');
        }

        $rsa = [
            'n' => gmp_export($modulus),
            'e' => gmp_export($exponent),
        ];

        $publicKeyResource = openssl_pkey_new([
            'rsa' => $rsa,
        ]);

        if (!$publicKeyResource) {
            throw new \RuntimeException('Failed to create public key: ' . openssl_error_string());
        }

        $publicKeyDetails = openssl_pkey_get_details($publicKeyResource);
        
        if ($publicKeyDetails === false || !isset($publicKeyDetails['key'])) {
            throw new \RuntimeException('Failed to get public key details');
        }
        
        return $publicKeyDetails['key'];
    }
}

