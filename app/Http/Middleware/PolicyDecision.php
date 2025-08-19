<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PolicyDecision
{
    public function handle(Request $request, Closure $next): Response
    {
        $rid   = $request->attributes->get('request_id');
        $sub   = $request->attributes->get('external_id');
        $roles = (array) data_get($request->attributes->get('token_data'), 'realm_access.roles', []);
        $roles = array_map('strtolower', $roles);

        $action      = (string) $request->attributes->get('action', 'read');
        $sensitivity = (string) $request->attributes->get('sensitivity', 'PII');
        $risk        = (int) $request->attributes->get('risk', 1);

        $obligations = [];

        // Règles simples :
        // - admin : passe tout
        if (in_array('admin', $roles, true)) {
            $obligations = [];
        } else {
            // PII: écriture ⇒ MFA si risk ≥ 1
            if ($sensitivity === 'PII' && $action === 'write' && $risk >= 1) {
                $obligations[] = 'mfa';
            }
        }

        Log::info('pdp.decision', compact('rid') + [
            'request_id'  => $rid,
            'sub'         => $sub,
            'roles'       => $roles,
            'action'      => $action,
            'sensitivity' => $sensitivity,
            'risk'        => $risk,
            'obligations' => $obligations,
        ]);

        // Évalue les obligations minimales
        if (in_array('mfa', $obligations, true)) {
            // Header indicateur posé par front/backoffice après MFA
            $mfaPassed = $request->headers->get('X-MFA-Passed');
            if (strtolower((string) $mfaPassed) !== 'true') {
                return response()->json([
                    'error'       => 'additional_auth_required',
                    'obligation'  => 'mfa',
                    'request_id'  => $rid,
                    'message'     => 'Strong customer authentication required for PII write.',
                ], 403);
            }
        }

        return $next($request);
    }
}
