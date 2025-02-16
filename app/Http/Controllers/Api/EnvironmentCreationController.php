<?php

namespace App\Http\Controllers\Api;

use App\Actions\Environments\CreateEnvironment;

class EnvironmentCreationController
{
    private CreateEnvironment $createEnvironment;

    public function __construct(CreateEnvironment $createEnvironment)
    {
        $this->createEnvironment = $createEnvironment;
    }

    public function store()
    {

        return response()->json($this->createNewAccountEnvironments->create($user));
    }
}
