<?php

namespace App\Http\Controllers\Idp;

use App\Http\Controllers\Controller;
use App\Services\Idp\OidcService;
use App\Services\Idp\TokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\Client;

class OidcController extends Controller
{
    public function __construct(
        private OidcService $oidcService,
        private TokenService $tokenService
    ) {}

    public function discovery()
    {
        return response()->json($this->oidcService->getDiscoveryDocument());
    }

    public function jwks()
    {
        return response()->json($this->oidcService->getJwks());
    }

    public function authorizeRequest(Request $request)
    {
        $request->validate([
            'client_id' => 'required',
            'redirect_uri' => 'required|url',
            'response_type' => 'required|in:code',
            'scope' => 'required',
            'state' => 'required',
            'code_challenge' => 'required_with:code_challenge_method',
            'code_challenge_method' => 'required_with:code_challenge|in:S256',
        ]);

        $client = Client::where('id', $request->client_id)
            ->where('revoked', false)
            ->firstOrFail();

        if (!str_contains($client->redirect, $request->redirect_uri)) {
            abort(400, 'Invalid redirect_uri');
        }

        if (!Auth::check()) {
            return redirect()->route('login', [
                'redirect_uri' => $request->fullUrl(),
            ]);
        }

        $scopes = explode(' ', $request->scope);
        if (!in_array('openid', $scopes)) {
            abort(400, 'openid scope is required');
        }

        return view('idp.consent', [
            'client' => $client,
            'scopes' => $scopes,
            'request' => $request->all(),
        ]);
    }

    public function approve(Request $request)
    {
        $request->validate([
            'client_id' => 'required',
            'redirect_uri' => 'required|url',
            'response_type' => 'required|in:code',
            'scope' => 'required',
            'state' => 'required',
            'code_challenge' => 'required_with:code_challenge_method',
            'code_challenge_method' => 'required_with:code_challenge|in:S256',
        ]);

        $code = $this->tokenService->createAuthorizationCode(
            Auth::user(),
            $request->all()
        );

        $redirectUri = $request->redirect_uri . '?' . http_build_query([
            'code' => $code,
            'state' => $request->state,
        ]);

        return redirect($redirectUri);
    }

    public function token(Request $request)
    {
        return $this->tokenService->issueToken($request);
    }

    public function userinfo(Request $request)
    {
        $user = $request->user();
        
        return response()->json([
            'sub' => (string) $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified' => $user->email_verified_at !== null,
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->has('post_logout_redirect_uri')) {
            return redirect($request->post_logout_redirect_uri);
        }

        return redirect('/');
    }
}

