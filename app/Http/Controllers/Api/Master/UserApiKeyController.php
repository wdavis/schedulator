<?php

namespace App\Http\Controllers\Api\Master;

use App\Actions\ApiKeys\GetUserApiKeys;
use App\Http\Resources\ApiKeyResource;

class UserApiKeyController
{
    private GetUserApiKeys $getUserApiKeys;

    public function __construct(GetUserApiKeys $getUserApiKeys)
    {
        $this->getUserApiKeys = $getUserApiKeys;
    }

    public function index($userId)
    {
        return ApiKeyResource::collection($this->getUserApiKeys->get($userId));
    }
}
