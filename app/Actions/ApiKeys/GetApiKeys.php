<?php

namespace App\Actions\ApiKeys;

use App\Models\ApiKey;
use App\Models\Environment;
use App\Models\User;

class GetApiKeys
{
    public function get(Environment $environment)
    {
        // todo for now just returning something, will need to fix once we can pass an environment in/run migrations fresh
        return ApiKey::all();
    }
}
