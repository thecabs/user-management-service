<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class Idempotency
{
    public function handle(Request $request, Closure $next, string $ttlSeconds = '600'): Response
    {
        // On applique seulement aux méthodes mutatives
        if (!in_array(strtoupper($request->method()), ['POST','PUT','PATCH','DELETE'], true)) {
            return $next($request);
        }

        $key = $request->header('Idempotency-Key') ?? $request->header('X-Idempotency-Key');
        if (!$key) {
            return response()->json([
                'error'   => 'idempotency_key_required',
                'message' => 'Provide Idempotency-Key header for write operations.',
            ], 409);
        }

        $prefix = (string) config('http.idempotency.prefix', 'idem:');
        $cacheKey = $prefix . sha1($request->path() . '|' . $key);

        // Cache::add retourne false si déjà présent
        $ttl = max(1, (int) $ttlSeconds);
        if (!Cache::add($cacheKey, '1', $ttl)) {
            return response()->json([
                'error'   => 'duplicate_request',
                'message' => 'This idempotency key has already been used.',
            ], 409);
        }

        return $next($request);
    }
}
