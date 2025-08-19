<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResourceTag
{
    public function handle(Request $request, Closure $next, string $tag = 'PII'): Response
    {
        $allowed = ['PII','FINANCIAL','KYC','ADMIN','PUBLIC'];
        $tag     = strtoupper($tag);
        if (!in_array($tag, $allowed, true)) {
            $tag = 'PUBLIC';
        }
        $request->attributes->set('resource_sensitivity', $tag);
        return $next($request);
    }
}
