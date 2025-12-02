<?php

namespace App\Http\Controllers\Rp;

use App\Helpers\OidcHelper;
use App\Http\Controllers\Controller;
use App\Services\Rp\OidcService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class OidcController extends Controller
{
    public function __construct(
        private OidcService $oidcService
    ) {}

    public function login(Request $request)
    {
        $state = Str::random(40);
        $nonce = Str::random(40);
        
        Session::put('oidc_state', $state);
        Session::put('oidc_nonce', $nonce);

        $codeVerifier = Str::random(128);
        $codeChallenge = OidcHelper::base64url_encode(hash('sha256', $codeVerifier, true));
        
        Session::put('oidc_code_verifier', $codeVerifier);

        $params = [
            'client_id' => config('oidc.rp.client_id'),
            'redirect_uri' => config('oidc.rp.redirect_uri'),
            'response_type' => 'code',
            'scope' => 'openid profile email',
            'state' => $state,
            'nonce' => $nonce,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ];

        $authorizationUrl = config('oidc.rp.idp_authorization_endpoint') . '?' . http_build_query($params);

        return redirect($authorizationUrl);
    }

    public function callback(Request $request)
    {
        $request->validate([
            'code' => 'required',
            'state' => 'required',
        ]);

        $sessionState = Session::get('oidc_state');
        if ($sessionState !== $request->state) {
            abort(400, 'Invalid state parameter');
        }

        $codeVerifier = Session::get('oidc_code_verifier');
        $nonce = Session::get('oidc_nonce');

        if (!$codeVerifier) {
            abort(400, 'Missing code_verifier in session');
        }

        try {
            $tokens = $this->oidcService->exchangeCodeForTokens(
                $request->code,
                $codeVerifier,
                $request->state
            );
        } catch (\Exception $e) {
            abort(500, 'Failed to exchange authorization code: ' . $e->getMessage());
        }

        if (!isset($tokens['id_token'])) {
            abort(500, 'Invalid token response: missing id_token');
        }

        $idToken = $tokens['id_token'];
        
        try {
            $userInfo = $this->oidcService->verifyIdToken($idToken, $nonce);
        } catch (\Exception $e) {
            abort(400, 'Invalid ID token: ' . $e->getMessage());
        }

        Session::put('oidc_user', $userInfo);
        Session::put('oidc_access_token', $tokens['access_token']);
        Session::put('oidc_refresh_token', $tokens['refresh_token'] ?? null);
        Session::forget(['oidc_state', 'oidc_nonce', 'oidc_code_verifier']);

        return redirect()->route('rp.home');
    }

    public function logout(Request $request)
    {
        Session::forget(['oidc_user', 'oidc_access_token', 'oidc_refresh_token']);
        
        $logoutUrl = config('oidc.rp.idp_end_session_endpoint') . '?' . http_build_query([
            'post_logout_redirect_uri' => route('rp.home'),
        ]);

        return redirect($logoutUrl);
    }
}

