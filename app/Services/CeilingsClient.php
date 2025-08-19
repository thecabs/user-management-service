<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class CeilingsClient
{
    public function __construct(
        private KeycloakTokenService $kc
    ) {}

    public function ensureDefault(string $externalId, string $currency = 'XAF'): bool
    {
        $base = rtrim(config('services.ceiling.url'), '/');
        $url  = $base.'/api/internal/ceilings/ensure';

        $token = $this->kc->getServiceToken();

        $resp = Http::timeout((int) config('services.ceiling.timeout', 5))
            ->acceptJson()
            ->withToken($token)
            ->withHeader('Idempotency-Key', (string) \Illuminate\Support\Str::uuid())
            ->post($url, [
                'external_id' => $externalId,
                'currency'    => $currency,
            ]);

        return $resp->ok() && (bool) $resp->json('success', false);
    }
}
