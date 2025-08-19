<?php

return [
    // Timeouts réseau par défaut (HTTP clients internes)
    'timeout' => (int) env('HTTP_TIMEOUT', 20),

    // Idempotency middleware settings
    'idempotency' => [
        'prefix' => env('HTTP_IDEM_PREFIX', 'idem:'),
        'ttl'    => (int) env('HTTP_IDEM_TTL', 600),
    ],
];
