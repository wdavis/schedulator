<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-Api-Key');

        if (!$apiKey) {
            return response()->json(['message' => 'API Key is required'], 401);
        }

        // load api key record but only load single environment with matching id for this user
        $apiKeyRecord = ApiKey::where('key', $apiKey)
            ->whereHas('user', function($query) {
                $query->where('api_active', true);
            })
            ->with('user','environment')->first();

        if (!$apiKeyRecord) {
            return response()->json(['message' => 'Invalid API Key'], 401);
        }

//        $environmentName = $request->header('X-Environment');
//
//        if (!$environmentName) {
//            return response()->json(['message' => 'Environment is required'], 400);
//        }

        $environment = $apiKeyRecord->environment;

        if (!$environment) {
            return response()->json(['message' => 'Invalid environment'], 400);
        }

        // Store the user and environment in the request for further processing
        // these are accessible in the controller via $request->attributes->get('user')
        $request->attributes->set('user', $apiKeyRecord->user);
        $request->attributes->set('environment', $environment);

        return $next($request);
    }
}
