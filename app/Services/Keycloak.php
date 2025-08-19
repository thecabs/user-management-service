<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class Keycloak
{
    protected string $baseUrl;
    protected string $realm;
    protected string $clientId;
    protected string $clientSecret;

    public function __construct()
    {
        $this->baseUrl = config('services.keycloak.base_url');
        $this->realm = config('services.keycloak.realm');
        $this->clientId = config('services.keycloak.client_id');
        $this->clientSecret = config('services.keycloak.client_secret');
    }

    public function getToken(string $username, string $password): array
    {
        $response = Http::asForm()->post("{$this->baseUrl}/realms/{$this->realm}/protocol/openid-connect/token", [
            'grant_type' => 'password',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'username' => $username,
            'password' => $password,
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception('Unable to get token: ' . $response->body());
    }
}
