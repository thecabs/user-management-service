<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public const HOME = '/home';

    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            // 60/min par utilisateur (JWT sub) ou IP
            $by = $request->attributes->get('external_id') ?: $request->ip();
            return Limit::perMinute(60)->by($by);
        });

        RateLimiter::for('pii-write', function (Request $request) {
            // opérations sensibles (PII) : 20/min
            $by = $request->attributes->get('external_id') ?: $request->ip();
            return Limit::perMinute(20)->by($by);
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
