<?php

namespace App\Services\Rp;

use App\Services\Rp\DiscoveryService;
use App\Services\Rp\JwtVerificationService;
use GuzzleHttp\Client;

class OidcService
{
    public function __construct(
        private DiscoveryService $discoveryService,
        private JwtVerificationService $jwtVerificationService
    ) {}

    public function exchangeCodeForTokens(string $code, string $codeVerifier, string $state): array
    {
        $discovery = $this->discoveryService->getDiscovery();
        
        if (!isset($discovery['token_endpoint'])) {
            throw new \RuntimeException('Token endpoint not found in discovery document');
        }

        $redirectUri = config('oidc.rp.redirect_uri');
        $clientId = config('oidc.rp.client_id');
        $clientSecret = config('oidc.rp.client_secret');

        if (!$redirectUri || !$clientId || !$clientSecret) {
            throw new \RuntimeException('OIDC client configuration is incomplete');
        }
        
        $client = new Client();
        
        try {
            $response = $client->post($discovery['token_endpoint'], [
                'form_params' => [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'redirect_uri' => $redirectUri,
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'code_verifier' => $codeVerifier,
                ],
            ]);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $message = 'Failed to request token endpoint';
            if ($e->hasResponse()) {
                $message .= ': ' . $e->getResponse()->getBody()->getContents();
            }
            throw new \RuntimeException($message, 0, $e);
        }

        $body = $response->getBody()->getContents();
        $tokens = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON response from token endpoint: ' . json_last_error_msg());
        }

        return $tokens;
    }

    public function verifyIdToken(string $idToken, ?string $nonce): array
    {
        return $this->jwtVerificationService->verify($idToken, $nonce);
    }
}

