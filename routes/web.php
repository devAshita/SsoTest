<?php

use Illuminate\Support\Facades\Route;

if (config('oidc.is_idp')) {
    require __DIR__.'/idp.php';
} else {
    require __DIR__.'/rp.php';
}

