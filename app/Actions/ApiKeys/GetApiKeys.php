<?php

namespace App\Actions\ApiKeys;

use App\Models\ApiKey;
use App\Models\Environment;

class GetApiKeys
{
    public function get(Environment $environment)
    {
        return ApiKey::where('environment_id', $environment->id)
            ->where('user_id', $environment->user_id)
            ->with('environment')
            ->get();
    }
}
