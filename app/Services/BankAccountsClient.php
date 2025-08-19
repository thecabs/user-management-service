<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class BankAccountsClient
{
    public function __construct(
        private KeycloakTokenService $kc
    ) {}

    public function isBankClient(string $externalId): bool
    {
        $base = rtrim(config('services.bankaccount.url'), '/');
        $url  = $base.'/api/internal/accounts/status/'.$externalId;

        $token = $this->kc->getServiceToken(); // client_credentials

        $resp = Http::timeout((int) config('services.bankaccount.timeout', 5))
            ->acceptJson()
            ->withToken($token)
            ->get($url);

        if ($resp->failed()) {
            // ZT: fail-closed (refuser si doute) -> tu peux loguer ici
            return false;
        }

        return (bool) $resp->json('verified', false);
    }
}
