<?php

namespace App\Http\Controllers\Api\Master;

use App\Actions\Account\CreateNewAccount;
use Illuminate\Foundation\Validation\ValidatesRequests;

class AccountCreationController
{
    use ValidatesRequests;

    private CreateNewAccount $createNewAccount;

    public function __construct(CreateNewAccount $createNewAccount)
    {
        $this->createNewAccount = $createNewAccount;
    }

    public function store()
    {
        $request = $this->validate(request(), [
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        return response()->json($this->createNewAccount->create(
            $request['name'],
            $request['email'],
            $request['password']
        ));
    }
}
