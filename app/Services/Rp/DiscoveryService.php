<?php

namespace App\Services\Rp;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;

class DiscoveryService
{
    public function getDiscovery(): array
    {
        $discoveryUrl = config('oidc.rp.idp_discovery_url');
        
        if (!$discoveryUrl) {
            throw new \RuntimeException('OIDC discovery URL not configured');
        }

        return Cache::remember('oidc_discovery', 3600, function () use ($discoveryUrl) {
            $client = new Client();
            
            try {
                $response = $client->get($discoveryUrl);
            } catch (\GuzzleHttp\Exception\RequestException $e) {
                throw new \RuntimeException('Failed to fetch discovery document: ' . $e->getMessage(), 0, $e);
            }
            
            $body = $response->getBody()->getContents();
            $discovery = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Invalid JSON in discovery document: ' . json_last_error_msg());
            }

            return $discovery;
        });
    }
}

