<?php

return [
    'is_idp' => env('IS_IDP', true),
    
    'idp' => [
        'issuer' => env('OIDC_ISSUER', env('APP_URL')),
        'authorization_endpoint' => env('OIDC_AUTHORIZATION_ENDPOINT', env('APP_URL') . '/oauth/authorize'),
        'token_endpoint' => env('OIDC_TOKEN_ENDPOINT', env('APP_URL') . '/oauth/token'),
        'userinfo_endpoint' => env('OIDC_USERINFO_ENDPOINT', env('APP_URL') . '/oauth/userinfo'),
        'end_session_endpoint' => env('OIDC_END_SESSION_ENDPOINT', env('APP_URL') . '/oauth/logout'),
        'jwks_uri' => env('OIDC_JWKS_URI', env('APP_URL') . '/.well-known/jwks.json'),
        'id_token_lifetime' => env('OIDC_ID_TOKEN_LIFETIME', 3600),
        'access_token_lifetime' => env('OIDC_ACCESS_TOKEN_LIFETIME', 3600),
        'refresh_token_lifetime' => env('OIDC_REFRESH_TOKEN_LIFETIME', 2592000),
    ],
    
    'rp' => [
        'idp_issuer' => env('OIDC_IDP_ISSUER'),
        'idp_discovery_url' => env('OIDC_IDP_DISCOVERY_URL'),
        'idp_authorization_endpoint' => env('OIDC_IDP_AUTHORIZATION_ENDPOINT'),
        'idp_token_endpoint' => env('OIDC_IDP_TOKEN_ENDPOINT'),
        'idp_userinfo_endpoint' => env('OIDC_IDP_USERINFO_ENDPOINT'),
        'idp_end_session_endpoint' => env('OIDC_IDP_END_SESSION_ENDPOINT'),
        'idp_jwks_uri' => env('OIDC_IDP_JWKS_URI'),
        'client_id' => env('OIDC_CLIENT_ID'),
        'client_secret' => env('OIDC_CLIENT_SECRET'),
        'redirect_uri' => env('OIDC_REDIRECT_URI'),
        'use_pkce' => env('OIDC_USE_PKCE', true),
        'code_challenge_method' => env('OIDC_CODE_CHALLENGE_METHOD', 'S256'),
    ],
];

