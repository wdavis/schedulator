<?php

namespace App\Actions\Services;

use App\Models\Environment;
use App\Models\Service;
use App\Models\User;

class CreateService
{
    public function create(User $user, Environment $environment, string $name): Service
    {
        return $environment->services()->create([
            'name' => $name,
        ]);
    }
}
