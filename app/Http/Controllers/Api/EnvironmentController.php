<?php

namespace App\Http\Controllers\Api;

use App\Actions\Environments\CreateEnvironment;
use App\Actions\Environments\GetEnvironments;
use App\Actions\Environments\RemoveEnvironment;
use App\Traits\InteractsWithEnvironment;

class EnvironmentController
{
    use InteractsWithEnvironment;

    private GetEnvironments $getEnvironments;
    private CreateEnvironment $createEnvironment;
    private RemoveEnvironment $removeEnvironment;

    public function __construct(GetEnvironments $getEnvironments, \App\Actions\Environments\CreateEnvironment $createEnvironment, \App\Actions\Environments\RemoveEnvironment $removeEnvironment)
    {
        $this->getEnvironments = $getEnvironments;
        $this->createEnvironment = $createEnvironment;
        $this->removeEnvironment = $removeEnvironment;
    }

    public function index()
    {
        return $this->getEnvironments->get(
            user: $this->getApiUser(),
            currentEnvironmentId: $this->getApiEnvironmentId()
        );
    }

    public function store()
    {
        return $this->createEnvironment->create(
            userId: $this->getApiUserId(),
            environmentName: request('name')
        );
    }

    public function destroy(string $environmentId)
    {
        $this->removeEnvironment->remove(
            userId: $this->getApiUserId(),
            environmentId: $environmentId
        );
    }
}
