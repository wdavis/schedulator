<?php

namespace App\Actions\Account;

use App\Models\ApiKey;
use App\Models\Environment;
use App\Models\User;
use Illuminate\Support\Str;

class CreateNewAccountEnvironments
{
    public function create(User $user)
    {
        // first check which environments they have
        $user->load('environments');

        $environments = [];

        if($user->environments->filter(fn($env) => $env->name === 'production')->count() > 0) {
            $prodEnv = Environment::create([
                'name' => "production",
                'user_id' => $user->id,
            ]);

            $environments[] = $prodEnv;
        }

        if($user->environments->filter(fn($env) => $env->name === 'staging')->count() > 0) {
            $stagingEnv = Environment::create([
                'name' => "staging",
                'user_id' => $user->id,
            ]);

            $environments[] = $stagingEnv;
        }

        if($user->environments->filter(fn($env) => $env->name === 'dev')->count() > 0) {
            $devEnv = Environment::create([
                'name' => "dev",
                'user_id' => $user->id,
            ]);

            $environments[] = $devEnv;
        }

        $responseEnvs = [];

        // create default service for each environment
        foreach ($environments as $environment) {

            $randomKey = Str::random(64);

            $apiKey = ApiKey::create([
                'key' => "{$environment->name}-{$randomKey}",
                'user_id' => $user->id,
                'environment_id' => $environment->id,
            ]);

            $service = $environment->services()->create([
                'name' => "Default Service for {$environment->name}",
            ]);

            $responseEnvs[$environment->name] = [
                'api_key' => $apiKey->key,
                'service_id' => $service->id,
            ];
        }

        $user->api_active = true;
        $user->save();

        return $responseEnvs;
    }
}
