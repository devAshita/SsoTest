<?php

use App\Http\Controllers\Rp\HomeController;
use App\Http\Controllers\Rp\OidcController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('rp.home');
Route::get('/login', [OidcController::class, 'login'])->name('rp.login');
Route::get('/oauth/callback', [OidcController::class, 'callback'])->name('rp.callback');
Route::post('/logout', [OidcController::class, 'logout'])->name('rp.logout');

