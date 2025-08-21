<?php

namespace App\Http\Middleware;

use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Auth\GenericUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckJWTFromKeycloak
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        if (!$token) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $rawKey = (string) config('keycloak.public_key');
            if ($rawKey === '') {
                return response()->json(['error' => 'Keycloak public key missing'], 500);
            }

            $pem = "-----BEGIN PUBLIC KEY-----\n".wordwrap($rawKey, 64, "\n", true)."\n-----END PUBLIC KEY-----";
            $decoded = JWT::decode($token, new Key($pem, 'RS256'));

            $realmAccess = isset($decoded->realm_access) ? (array) $decoded->realm_access : [];
            $roles       = isset($realmAccess['roles']) ? (array) $realmAccess['roles'] : [];

            // expose un "user" sans DB
            $genericUser = new GenericUser([
                'sub'                 => $decoded->sub ?? null,
                'preferred_username'  => $decoded->preferred_username ?? null,
                'email'               => $decoded->email ?? null,
                'realm_access'        => ['roles' => $roles],
                'token'               => $decoded,
            ]);

            // for request()->user()
            $request->setUserResolver(fn () => $genericUser);
            // for Auth::user() dans les contrôleurs
            Auth::setUser($genericUser);

            // attributes pour les autres middlewares
            $request->attributes->add([
                'external_id' => $decoded->sub ?? null,
                'agency_id'   => $decoded->agency_id ?? null, // si mappé dans le token
                'token_data'  => $decoded,
            ]);

            return $next($request);

        } catch (\Throwable $e) {
            return response()->json(['error' => 'Invalid token', 'message' => $e->getMessage()], 401);
        }
    }
}
