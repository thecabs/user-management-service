<?php

return [

    'mailgun' => [
        'domain'   => env('MAILGUN_DOMAIN'),
        'secret'   => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme'   => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // ✅ Ajout clé Keycloak pour nos services
    'keycloak' => [
        'base_url'      => env('KEYCLOAK_BASE_URL', 'http://127.0.0.1:8000'),
        'realm'         => env('KEYCLOAK_REALM', 'sara-realm'),
        'client_id'     => env('KEYCLOAK_CLIENT_ID', 'userm-service'),
        'client_secret' => env('KEYCLOAK_CLIENT_SECRET'), // null si public client
    ],
    'bankaccount' => [
    'url'     => env('BANKACCOUNT_SVC_URL', 'http://127.0.0.1:8000'),
    'timeout' => env('BANKACCOUNT_HTTP_TIMEOUT', 5),
],
'ceiling' => [
    'url'     => env('CEILING_SVC_URL', 'http://127.0.0.1:8001'),
    'timeout' => env('CEILING_HTTP_TIMEOUT', 5),
],

];
