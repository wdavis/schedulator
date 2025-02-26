<?php

namespace App\Actions\Account;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateNewAccount
{
    private CreateNewAccountEnvironments $createNewAccountEnvironments;

    public function __construct(CreateNewAccountEnvironments $createNewAccountEnvironments)
    {
        $this->createNewAccountEnvironments = $createNewAccountEnvironments;
    }

    public function create(string $name, string $email, string $password, int $defaultServiceDuration = 15): array
    {
        $user = null;
        $keys = null;

        DB::transaction(function () use ($name, $email, $password, &$user, &$keys, $defaultServiceDuration) {
            $user = $this->createUser($name, $email, $password);
            $keys = $this->createEnvironments($user, $defaultServiceDuration);
        });

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'keys' => $keys,
            'message' => 'Keys created. Now create your resources and schedules.',
        ];
    }

    private function createUser(string $name, string $email, string $password): User
    {
        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt($password),
            'api_active' => true,
        ]);

    }

    private function createEnvironments(User $user, int $defaultServiceDuration): array
    {
        return $this->createNewAccountEnvironments->create($user, $defaultServiceDuration);
    }
}
