<?php
declare(strict_types=1);

namespace App\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;

class KeycloakTokenService
{
    private PendingRequest $http;

    private string $baseUrl;
    private string $realm;
    private string $clientId;
    private ?string $clientSecret;

    public function __construct(HttpFactory $factory, CacheRepository $cache)
    {
        $this->http = $factory->timeout((int) config('http.timeout', 20))
                              ->retry(2, 200);

        $this->cache        = $cache;
        $this->baseUrl      = rtrim((string) config('services.keycloak.base_url'), '/');
        $this->realm        = (string) config('services.keycloak.realm');
        $this->clientId     = (string) config('services.keycloak.client_id');
        $this->clientSecret = config('services.keycloak.client_secret');
    }

    public function getServiceToken(?string $scope = null): string
    {
        $cacheKey = $this->cacheKey($this->clientId, $scope);
        if ($tok = $this->cache->get($cacheKey)) return (string) $tok;

        $url  = "{$this->baseUrl}/realms/{$this->realm}/protocol/openid-connect/token";
        $form = ['grant_type' => 'client_credentials', 'client_id' => $this->clientId];
        if (!empty($this->clientSecret)) $form['client_secret'] = $this->clientSecret;
        if (!empty($scope)) $form['scope'] = $scope;

        $resp = $this->http->asForm()->post($url, $form);
        $resp->throw();

        $access = (string) $resp->json('access_token');
        $ttl    = max(60, ((int) $resp->json('expires_in', 300)) - 30);
        $this->cache->put($cacheKey, $access, $ttl);

        return $access;
    }

    private CacheRepository $cache;
    private function cacheKey(string $clientId, ?string $scope): string
    {
        return 'kc:svc_token:'. $this->realm .':'. $clientId .':'. ($scope ?: 'default');
    }
}
