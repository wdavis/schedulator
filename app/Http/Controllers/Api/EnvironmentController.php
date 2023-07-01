<?php

namespace App\Http\Controllers\Api;

use App\Actions\Environments\GetEnvironments;

class EnvironmentController
{
    private GetEnvironments $getEnvironments;

    /**
     * @param GetEnvironments $getEnvironments
     */
    public function __construct(GetEnvironments $getEnvironments)
    {
        $this->getEnvironments = $getEnvironments;
    }

    public function index()
    {
        return $this->getEnvironments->get(
            request()->attributes->get('user'),
            request()->attributes->get('environment')->id
        );
    }
}
