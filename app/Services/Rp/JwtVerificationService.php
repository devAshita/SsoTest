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

        $expectedIssuer = rtrim(config('oidc.rp.idp_issuer'), '/');
        $actualIssuer = rtrim($decoded->iss, '/');
        
        if ($actualIssuer !== $expectedIssuer) {
            throw new \Exception("Invalid issuer. Expected: '{$expectedIssuer}', Got: '{$actualIssuer}'");
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

        if (isset($jwk['kty']) && $jwk['kty'] !== 'RSA') {
            throw new \RuntimeException('Unsupported key type: ' . $jwk['kty']);
        }

        try {
            $n = OidcHelper::base64url_decode($jwk['n']);
            $e = OidcHelper::base64url_decode($jwk['e']);
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to decode JWK: ' . $e->getMessage(), 0, $e);
        }

        // GMPを使用してモジュラスと指数を取得
        $modulus = gmp_import($n);
        $exponent = gmp_import($e);

        if ($modulus === false || $exponent === false) {
            throw new \RuntimeException('Failed to import RSA parameters');
        }

        // DER形式の公開鍵を構築
        $der = $this->buildDerPublicKey($modulus, $exponent);
        
        // PEM形式に変換
        $pem = "-----BEGIN PUBLIC KEY-----\n";
        $pem .= chunk_split(base64_encode($der), 64, "\n");
        $pem .= "-----END PUBLIC KEY-----\n";
        
        return $pem;
    }

    /**
     * RSA公開鍵をDER形式で構築
     */
    private function buildDerPublicKey($modulus, $exponent): string
    {
        // ASN.1 DER形式でRSA公開鍵を構築
        // SEQUENCE { SEQUENCE { OID, NULL }, BIT STRING { SEQUENCE { INTEGER, INTEGER } } }
        
        // RSA OID: 1.2.840.113549.1.1.1
        $rsaOid = "\x30\x0d\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01\x05\x00";
        
        // モジュラスと指数をDER形式のINTEGERに変換
        $modulusDer = $this->derEncodeInteger($modulus);
        $exponentDer = $this->derEncodeInteger($exponent);
        
        // SEQUENCE { INTEGER modulus, INTEGER exponent }
        $rsaKeySequence = "\x30" . $this->derEncodeLength(strlen($modulusDer) + strlen($exponentDer)) 
                         . $modulusDer . $exponentDer;
        
        // BIT STRING
        $bitString = "\x03" . $this->derEncodeLength(strlen($rsaKeySequence) + 1) 
                    . "\x00" . $rsaKeySequence;
        
        // SEQUENCE { SEQUENCE { OID, NULL }, BIT STRING }
        $publicKeySequence = "\x30" . $this->derEncodeLength(strlen($rsaOid) + strlen($bitString))
                           . $rsaOid . $bitString;
        
        return $publicKeySequence;
    }

    /**
     * GMP整数をDER形式のINTEGERに変換
     */
    private function derEncodeInteger($gmpInt): string
    {
        $bytes = gmp_export($gmpInt, 1, GMP_MSW_FIRST | GMP_BIG_ENDIAN);
        
        // 最上位ビットが1の場合、先頭に0を追加（負数として解釈されないように）
        if (ord($bytes[0]) & 0x80) {
            $bytes = "\x00" . $bytes;
        }
        
        return "\x02" . $this->derEncodeLength(strlen($bytes)) . $bytes;
    }

    /**
     * DER形式の長さをエンコード
     */
    private function derEncodeLength(int $length): string
    {
        if ($length < 0x80) {
            return chr($length);
        }
        
        $bytes = '';
        $temp = $length;
        while ($temp > 0) {
            $bytes = chr($temp & 0xff) . $bytes;
            $temp >>= 8;
        }
        
        return chr(0x80 | strlen($bytes)) . $bytes;
    }
}

