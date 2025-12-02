<?php

namespace App\Services\Rp;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;

class DiscoveryService
{
    public function getDiscovery(): array
    {
        return Cache::remember('oidc_discovery', 3600, function () {
            $client = new Client();
            $response = $client->get(config('oidc.rp.idp_discovery_url'));
            
            return json_decode($response->getBody()->getContents(), true);
        });
    }
}

