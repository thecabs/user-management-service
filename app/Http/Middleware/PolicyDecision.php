<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class PolicyDecision
{
    public function handle(Request $request, Closure $next): Response
    {
        // ---------------------------------------------------------------------
        // Correlation ID
        // ---------------------------------------------------------------------
        $rid = $request->attributes->get('request_id');
        if (!$rid) {
            $rid = (string) Str::uuid();
            $request->attributes->set('request_id', $rid);
        }

        // ---------------------------------------------------------------------
        // Contexte identité & requête
        // ---------------------------------------------------------------------
        /** @var array<string,mixed>|object $token */
        $token = $request->attributes->get('token_data', []);

        $sub   = (string) ($request->attributes->get('external_id') ?? data_get($token, 'sub', ''));
        $roles = array_map('strtolower', (array) data_get($token, 'realm_access.roles', []));

        // Action normalisée: attr -> zt.action -> déduit de la méthode
        $method     = strtoupper($request->getMethod());
        $actionAttr = (string) ($request->attributes->get('action') ?? $request->attributes->get('zt.action', ''));
        $action     = $actionAttr !== '' ? $actionAttr : (in_array($method, ['GET','HEAD','OPTIONS'], true) ? 'read' : 'write');

        // Sensibilité: zt.resource.sensitivity -> sensitivity -> défaut PII
        $sensAttr   = (string) ($request->attributes->get('zt.resource.sensitivity') ?? $request->attributes->get('sensitivity') ?? 'PII');
        $sensitivity= strtoupper($sensAttr);

        $risk = (int) ($request->attributes->get('risk') ?? 1);

        // Route name (exemptions step-up)
        $routeName    = optional($request->route())->getName();
        $exemptRoutes = (array) config('security.stepup_exempt_routes', []);
        $isExempt     = $routeName && in_array($routeName, $exemptRoutes, true);

        // ---------------------------------------------------------------------
        // Signals d'authentification
        // ---------------------------------------------------------------------
        $acr        = (string) data_get($token, 'acr', '');
        $amrRaw     = data_get($token, 'amr', []);
        $amr        = is_array($amrRaw) ? $amrRaw : (array) $amrRaw;
        $amrLower   = array_map('strtolower', $amr);

        $authTime   = (int) data_get($token, 'auth_time', 0);
        $issuedAt   = (int) data_get($token, 'iat', 0);
        $authMoment = max($authTime, $issuedAt);

        // ---------------------------------------------------------------------
        // Fenêtre de fraîcheur MFA (par défaut 10 min) – configurable
        // ---------------------------------------------------------------------
        $ttl = (int) (config('security.stepup_ttl', (int) env('STEPUP_TTL_SECONDS', 600)));
        $ttl = max($ttl, 60); // minimum 60s

        $recent = ($authMoment > 0) && ((time() - $authMoment) < $ttl);

        // Preuve MFA intrinsèque au token (AMR/ACR)
        $acrNum = is_numeric($acr) ? (int) $acr : 0;
        $hasMfaEvidence =
            in_array('mfa', $amrLower, true)     ||
            in_array('otp', $amrLower, true)     ||
            in_array('totp', $amrLower, true)    ||
            in_array('hwk', $amrLower, true)     ||
            in_array('webauthn', $amrLower, true)||
            in_array('sms', $amrLower, true)     ||
            in_array('email', $amrLower, true)   ||
            ($acrNum >= 2);

        // ✅ CHANGEMENT: MFA satisfaite si "récente" OU "évidence"
        $mfaSatisfied = $recent || $hasMfaEvidence;

        // ---------------------------------------------------------------------
        // Fallback DEV (uniquement hors prod) pour tests Postman
        // ---------------------------------------------------------------------
        if (!app()->environment('production')) {
            $mfaHeader = strtolower((string) $request->headers->get('X-MFA-Passed', ''));
            if ($mfaHeader === 'true') {
                $mfaSatisfied = true;
            }
        }

        // ---------------------------------------------------------------------
        // Politique : WRITE sur PII => MFA fraîche requise pour ces rôles
        // ---------------------------------------------------------------------
        $rolesRequerantMfa = [
            'admin',
            'gfc',
            'agi',
            'directeur_agence',
            'client_bancaire',
            'client_non_bancaire',
        ];

        $obligations = [];

        if ($sensitivity === 'PII' && $action === 'write' && $risk >= 1) {
            if (!$isExempt && count(array_intersect($roles, $rolesRequerantMfa)) > 0) {
                if (!$mfaSatisfied) {
                    $obligations[] = 'mfa';
                }
            }
        }

        // ---------------------------------------------------------------------
        // Logs PDP structurés
        // ---------------------------------------------------------------------
        Log::info('pdp.decision', [
            'rid'              => $rid,
            'request_id'       => $rid,
            'sub'              => $sub,
            'roles'            => $roles,
            'route'            => $routeName,
            'method'           => $method,
            'path'             => $request->path(),
            'action'           => $action,
            'sensitivity'      => $sensitivity,
            'risk'             => $risk,
            'acr'              => $acr,
            'amr'              => $amr,
            'auth_time'        => $authTime,
            'iat'              => $issuedAt,
            'auth_moment'      => $authMoment,
            'ttl_sec'          => $ttl,
            'recent'           => $recent,
            'has_mfa_evidence' => $hasMfaEvidence,
            'mfa_satisfied'    => $mfaSatisfied,
            'exempt'           => (bool) $isExempt,
            'obligations'      => $obligations,
        ]);

        // ---------------------------------------------------------------------
        // Application des obligations minimales
        // ---------------------------------------------------------------------
        if (in_array('mfa', $obligations, true)) {
            return response()->json([
                'error'       => 'additional_auth_required',
                'obligation'  => 'mfa',
                'request_id'  => $rid,
                'ttl_sec'     => $ttl,
                'methods'     => ['otp'],
                'message'     => 'Strong customer authentication required for PII write.',
            ], 403);
        }

        // ---------------------------------------------------------------------
        // Suite de la chaîne + propagation du Request-Id
        // ---------------------------------------------------------------------
        $response = $next($request);
        try {
            $response->headers->set('X-Request-Id', $rid);
        } catch (\Throwable $e) {
            // no-op
        }

        return $response;
    }
}
