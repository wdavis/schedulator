<?php

namespace App\Http\Controllers\Api;

use App\Actions\ApiKeys\GetApiKeys;
use App\Http\Resources\ApiKeyResource;
use App\Traits\InteractsWithEnvironment;

class ApiKeyController
{
    use InteractsWithEnvironment;

    private GetApiKeys $getApiKeys;

    public function __construct(GetApiKeys $getApiKeys)
    {
        $this->getApiKeys = $getApiKeys;
    }

    public function index()
    {
        return ApiKeyResource::collection($this->getApiKeys->get(
            $this->getApiEnvironment()
        ));
    }
}
