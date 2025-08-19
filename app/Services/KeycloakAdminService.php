<?php
declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;

class KeycloakAdminService
{
    private PendingRequest $http;
    private string $baseUrl;
    private string $realm;

    public function __construct(HttpFactory $factory, KeycloakTokenService $tokens)
    {
        $this->baseUrl = rtrim((string) config('services.keycloak.base_url'), '/');
        $this->realm   = (string) config('services.keycloak.realm');

        $this->http = $factory->timeout((int) config('http.timeout', 20))
                              ->retry(2, 200)
                              ->acceptJson()
                              ->withToken($tokens->getServiceToken());
    }

    /** Retourne un tableau Keycloak (ou null si 404) */
    public function getUserById(string $externalId): ?array
    {
        $url  = "{$this->baseUrl}/admin/realms/{$this->realm}/users/{$externalId}";
        $resp = $this->http->get($url);
        if ($resp->status() === 404) return null;
        $resp->throw();
        return $resp->json();
    }
}
