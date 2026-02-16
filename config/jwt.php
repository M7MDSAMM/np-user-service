<?php

return [

    'issuer'   => env('JWT_ISSUER', 'user-service'),
    'audience'  => env('JWT_AUDIENCE', 'notification-platform'),
    'ttl'       => (int) env('JWT_TTL_SECONDS', 900), // 15 minutes

    'keys' => [
        'private' => storage_path('app/keys/jwt-private.pem'),
        'public'  => storage_path('app/keys/jwt-public.pem'),
    ],

];
