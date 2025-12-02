<?php

return [
    'private_key' => env('PASSPORT_PRIVATE_KEY'),
    'public_key' => env('PASSPORT_PUBLIC_KEY'),
    'client_uuids' => false,
    'storage_driver' => env('PASSPORT_STORAGE_DRIVER', 'database'),
];

