<?php

namespace App\Actions\ApiKeys;

use App\Models\ApiKey;

class GetUserApiKeys
{
    public function get(int $userId)
    {
        return ApiKey::where('user_id', $userId)
            ->with('environment')
            ->get();
    }
}
