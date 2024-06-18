<?php

namespace App\Http\Controllers\Api\Master;

use App\Actions\Account\CreateNewAccountEnvironments;
use App\Models\User;

class AccountEnvironmentCreationController
{
    private CreateNewAccountEnvironments $createNewAccountEnvironments;

    public function __construct(CreateNewAccountEnvironments $createNewAccountEnvironments)
    {
        $this->createNewAccountEnvironments = $createNewAccountEnvironments;
    }

    public function store($userId)
    {
        $user = User::find($userId);

        return response()->json($this->createNewAccountEnvironments->create($user));
    }
}
