<?php

return [
    'jwks_url'   => env('KEYCLOAK_REALM_URL') . '/protocol/openid-connect/certs',
    'issuer'     => env('KEYCLOAK_REALM_URL'),
    'client_id'  => env('KEYCLOAK_CLIENT_ID', 'userm-service'),
    'public_key' => env('KEYCLOAK_PUBLIC_KEY'),
];
