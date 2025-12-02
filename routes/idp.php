<?php

use App\Http\Controllers\Idp\AuthController;
use App\Http\Controllers\Idp\OidcController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('idp.home');
})->name('idp.home');

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/oauth/authorize', [OidcController::class, 'authorizeRequest'])->middleware('web');
Route::post('/oauth/authorize', [OidcController::class, 'approve'])->middleware('web')->name('oauth.authorize');
Route::post('/oauth/token', [OidcController::class, 'token']);
Route::get('/oauth/userinfo', [OidcController::class, 'userinfo'])->middleware('auth:api');
Route::get('/oauth/logout', [OidcController::class, 'logout'])->middleware('web');

Route::get('/.well-known/openid-configuration', [OidcController::class, 'discovery']);
Route::get('/.well-known/jwks.json', [OidcController::class, 'jwks']);
