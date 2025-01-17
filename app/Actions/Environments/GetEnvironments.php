<?php

namespace App\Actions\Environments;

use App\Models\Environment;
use App\Models\User;

class GetEnvironments
{
    public function get(User $user, ?string $currentEnvironmentId = null)
    {
        $user->load('environments');

        return $user->environments->map(function (Environment $environment) use ($currentEnvironmentId) {
            return [
                'id' => $environment->id,
                'name' => $environment->name,
                'created_at' => $environment->created_at,
                'updated_at' => $environment->updated_at,
                'current' => $environment->id === $currentEnvironmentId,
            ];
        });
    }
}
