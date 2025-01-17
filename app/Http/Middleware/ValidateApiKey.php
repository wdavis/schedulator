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
     * @param  string|null  $requireMaster
     */
    public function handle(Request $request, Closure $next, $requireMaster = null): Response
    {
        $apiKey = $request->header('X-Api-Key');

        if (is_null($apiKey)) {
            $apiKey = request()->header('Authorization');
            if (! is_null($apiKey)) {
                $apiKey = str_replace('Bearer ', '', $apiKey);
            }
        }

        if (! $apiKey) {
            return response()->json(['message' => 'API Key is required'], 401);
        }

        // Load the API key record and related user and environment
        $apiKeyRecord = ApiKey::where('key', $apiKey)
            ->whereHas('user', function ($query) {
                $query->where('api_active', true);
            })
            ->with('user', 'environment')
            ->first();

        if (! $apiKeyRecord) {
            return response()->json(['message' => 'Invalid API Key'], 403);
        }

        // Check if the master key is required and if the provided key is a master key
        if ($requireMaster === 'master' && ! $apiKeyRecord->is_master) {
            return response()->json(['message' => 'Invalid'], 403);
        }

        // If not a master key, ensure the environment is valid
        if (! $apiKeyRecord->is_master) {
            $environment = $apiKeyRecord->environment;

            if (! $environment) {
                return response()->json(['message' => 'Invalid environment'], 400);
            }

            $request->attributes->set('environment', $environment);
        }

        // Store the user in the request for further processing
        $request->attributes->set('user', $apiKeyRecord->user);

        return $next($request);
    }
}
