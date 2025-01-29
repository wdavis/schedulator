<?php

namespace App\Actions\Environments;

use App\Exceptions\EnvironmentAlreadyExistsException;
use App\Exceptions\InvalidEnvironmentNameException;
use App\Models\ApiKey;
use App\Models\Environment;
use Illuminate\Support\Str;

class CreateEnvironment
{
    public function create(int $userId, string $environmentName, int $defaultServiceDuration = 15): array
    {
        // environment name can only contain alpha characters
        if (! ctype_alpha($environmentName)) {
            throw new InvalidEnvironmentNameException('Environment name can only contain alpha characters');
        }

        // check if environment exists first by name
        if (Environment::where('name', $environmentName)->where('user_id', $userId)->count() > 0) {
            throw new EnvironmentAlreadyExistsException("Environment with name {$environmentName} already exists");
        }

        $environment = Environment::create([
            'name' => $environmentName,
            'user_id' => $userId,
        ]);

        $randomKey = Str::random(64);

        $apiKey = ApiKey::create([
            'key' => "{$environmentName}-{$randomKey}",
            'user_id' => $userId,
            'environment_id' => $environment->id,
        ]);

        $service = $environment->services()->create([
            'name' => "Default Service for {$environment->name}",
            'duration' => $defaultServiceDuration,
        ]);

        return [
            'api_key' => $apiKey->key,
            'service_id' => $service->id,
            'environment_id' => $environment->id,
        ];
    }
}
