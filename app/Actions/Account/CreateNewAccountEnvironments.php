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
        $environments = [];

        $prodEnv = Environment::create([
            'name' => "production",
            'user_id' => $user->id,
        ]);

        $environments[] = $prodEnv;

        $stagingEnv = Environment::create([
            'name' => "staging",
            'user_id' => $user->id,
        ]);

        $environments[] = $stagingEnv;

        $devEnv = Environment::create([
            'name' => "dev",
            'user_id' => $user->id,
        ]);

        $environments[] = $devEnv;

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
