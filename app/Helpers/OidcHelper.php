<?php

namespace App\Helpers;

class OidcHelper
{
    public static function base64url_encode($data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function base64url_decode($data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}

