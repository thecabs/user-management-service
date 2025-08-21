<?php
return [
    // Fraîcheur MFA (secondes)
    'stepup_ttl' => (int) env('STEPUP_TTL_SECONDS', 600),

    // Endpoints de "création/init" où on ne force PAS OTP
    // (noms = ->name() dans routes/api.php)
    'stepup_exempt_routes' => [
        'onboarding.banking',        // POST /onboarding/banking-client
        'onboarding.non_banking',    // POST /onboarding/non-banking-client
        'devices.trust',             // POST /devices/trust
    ],
];
