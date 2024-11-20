<?php

namespace App\Actions\Environments;

use App\Models\Environment;

class RemoveEnvironment
{
    public function remove(int $userId, string $environmentId): void
    {
        $environment = Environment::where('id', $environmentId)->where('user_id', $userId)
            ->firstOrFail();

        // safeguard default account environments
        if(in_array($environment->name, ['production', 'staging', 'dev'])) {
            throw new \InvalidArgumentException("Cannot remove any base environments like production, staging or dev");
        }

        $environment->delete();
    }
}
