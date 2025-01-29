<?php

namespace App\Actions;

use App\Models\ApiKey;
use App\Models\Environment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateBaseAccount
{
    public function create(string $email, string $name)
    {
        DB::beginTransaction();

        // create environments
        $environments = [];
        $apiKeys = [];

        try {

            $user = User::create([
                'email' => $email,
                'password' => bcrypt('password'),
                'name' => $name,
                'api_active' => 't',
            ]);

            $prodEnv = Environment::create([
                'name' => 'prod',
                'user_id' => $user->id,
            ]);

            $environments[] = $prodEnv;

            $stagingEnv = Environment::create([
                'name' => 'staging',
                'user_id' => $user->id,
            ]);

            $environments[] = $stagingEnv;

            $devEnv = Environment::create([
                'name' => 'dev',
                'user_id' => $user->id,
            ]);

            $environments[] = $devEnv;

            // create default service for each environment
            foreach ($environments as $environment) {
                $service = $environment->services()->create([
                    'name' => "Default Service for {$environment->name}",
                ]);

                $randomKey = Str::random(32);

                $apiKey = ApiKey::create([
                    'key' => "{$environment->name}-{$randomKey}", // make sure we have a known key for testing
                    'user_id' => $user->id,
                    'environment_id' => $environment->id,
                ]);

                $apiKeys[] = "{$environment->name}-{$randomKey}";

                //            $environment->update([
                //                'default_service_id' => $service->id,
                //            ]);
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return [
            'user_id' => $user->id,
            'api_keys' => $apiKeys,
        ];
    }
}
