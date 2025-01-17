<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
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
     * Register any application services.
     */
    public function register(): void
    {
        //
        //        DB::enableQueryLog();

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        JsonResource::withoutWrapping();

        $this->bootRoute();
    }

    public function bootRoute(): void
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

    }
}
