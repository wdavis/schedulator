<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {

            // if the request has an authorization header, we will use the value of the header as the key
            // otherwise, we will use the IP address of the request as the key
            $key = $request->header('Authorization');

            if (is_null($key)) {
                $key = $request->ip();
            } else { // get the api key by removing the "Bearer " prefix
                $key = str_replace('Bearer ', '', $key);
            }

            // now determine the rate limit based on the prefix of the key (e.g. "production-", "staging-", "dev-")
            $rateLimit = 50;

            if (Str::startsWith($key, 'production-')) {
                $rateLimit = 8000;
            } elseif (app()->environment('local')) {
                $rateLimit = 3000;
            } elseif (Str::startsWith($key, 'staging-') || Str::startsWith($key, 'dev-') || Str::startsWith($key, 'master-')) {
                $rateLimit = 100;
            }

            return Limit::perMinute($rateLimit)->by($key);
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('v1')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
