<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ContextEnricher
{
    public function handle(Request $request, Closure $next): Response
    {
        // request_id stable
        $rid = $request->headers->get('X-Request-Id') ?: (string) Str::uuid();

        // action dérivée de la méthode HTTP
        $method = strtoupper($request->getMethod());
        $action = in_array($method, ['GET','HEAD','OPTIONS']) ? 'read' : 'write';

        // sensibilité posée par ResourceTag middleware (ou défaut)
        $sensitivity = $request->attributes->get('resource_sensitivity', 'PII');

        // petit score de "risk" (ex: header device-trust)
        $risk = 1;
        $deviceTrust = $request->headers->get('X-Device-Trust');
        if ($deviceTrust && strtolower($deviceTrust) === 'low') {
            $risk = 2; // plus restrictif
        }

        $request->attributes->set('request_id', $rid);
        $request->attributes->set('action', $action);
        $request->attributes->set('sensitivity', $sensitivity);
        $request->attributes->set('risk', $risk);

        // propage X-Request-Id
        $request->headers->set('X-Request-Id', $rid);

        return $next($request);
    }
}
