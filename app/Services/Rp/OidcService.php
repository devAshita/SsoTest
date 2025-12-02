<?php

namespace App\Services\Rp;

use App\Services\Rp\DiscoveryService;
use App\Services\Rp\JwtVerificationService;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;

class OidcService
{
    public function __construct(
        private DiscoveryService $discoveryService,
        private JwtVerificationService $jwtVerificationService
    ) {}

    public function exchangeCodeForTokens(string $code, string $codeVerifier, string $state): array
    {
        $discovery = $this->discoveryService->getDiscovery();
        
        $client = new Client();
        $response = $client->post($discovery['token_endpoint'], [
            'form_params' => [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => config('oidc.rp.redirect_uri'),
                'client_id' => config('oidc.rp.client_id'),
                'client_secret' => config('oidc.rp.client_secret'),
                'code_verifier' => $codeVerifier,
            ],
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    public function verifyIdToken(string $idToken, ?string $nonce): array
    {
        return $this->jwtVerificationService->verify($idToken, $nonce);
    }
}

