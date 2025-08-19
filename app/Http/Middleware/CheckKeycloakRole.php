<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckKeycloakRole
{
    public function handle(Request $request, Closure $next, $requiredRoleCsv)
    {
        $tokenData = $request->attributes->get('token_data');
        if (!$tokenData) {
            return response()->json(['error' => 'Access Denied - Token data missing'], 403);
        }

        $userRoles = $tokenData->realm_access->roles ?? [];
        $userRoles = array_map('strtolower', (array) $userRoles);
        $required  = array_map(fn($r) => strtolower(trim($r)), explode(',', (string) $requiredRoleCsv));

        foreach ($required as $r) {
            if (in_array($r, $userRoles, true)) {
                return $next($request);
            }
        }

        return response()->json([
            'error'          => 'Access Denied - Missing role',
            'required_roles' => $required,
            'user_roles'     => $userRoles,
        ], 403);
    }
}
