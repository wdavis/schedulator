<?php

namespace Tests\Helpers;

use App\Actions\Account\CreateNewAccount;
use App\Actions\Bookings\CreateBooking;
use App\Actions\Resources\CreateResource;
use App\Actions\UpdateSchedule;
use App\Models\Resource;
use App\Models\Service;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

trait CreatesTestAccounts
{
    public function createResource(string $name, string $environmentId, string $timezone = 'UTC', bool $active = true): Resource
    {
        $createResource = new CreateResource();
        return $createResource->create(
            name: $name,
            environmentId: $environmentId,
            active: $active,
            meta: [
                'timezone' => $timezone
            ]
        );
    }

    private function createAccount()
    {
        /** @var CreateNewAccount $createAccount */
        $createAccount = app(CreateNewAccount::class);
        $account = $createAccount->create(
            name: 'Test User',
            email: 'test@test.com',
            password: 'password'
        );

        // create production resource
        $prodResource = $this->createResource(
            name: $account['user']['name'],
            environmentId: $this->getEnvEnvironmentId('production', $account),
            timezone: 'UTC'
        );

        // create staging resource
        $stagingResource = $this->createResource(
            name: $account['user']['name'],
            environmentId: $this->getEnvEnvironmentId('staging', $account),
            timezone: 'UTC'
        );

        return [
            'user' => User::find($account['user']['id']),
            'account' => $account,
            'prodResource' => $prodResource,
            'prodService' => Service::find($this->getEnvServiceId('production', $account)),
            'prodEnvironmentId' => $this->getEnvEnvironmentId('production', $account),
            'prodApiKey' => $this->getApiKey('production', $account),
            'stagingResource' => $stagingResource,
            'stagingService' => Service::find($this->getEnvServiceId('staging', $account)),
            'stagingEnvironmentId' => $this->getEnvEnvironmentId('staging', $account),
            'stagingApiKey' => $this
        ];
    }

    private function getEnvEnvironmentId($environment, $account): string
    {
        return $account['keys'][$environment]['environment_id'];
    }

    private function getEnvServiceId($environment, $account): string
    {
        return $account['keys'][$environment]['service_id'];
    }

    private function getApiKey($environment, $account): string
    {
        return $account['keys'][$environment]['api_key'];
    }

    private function createSchedule(string $day, string $startTime, string $endTime, Resource $resource): Collection
    {
        /** @var UpdateSchedule $scheduler */
        $scheduler = app(UpdateSchedule::class);
        return $scheduler->execute(
            resource: $resource,
            scheduleData: [
                $day => [
                    [
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                    ]
                ],
            ]
        );
    }

    private function createBooking(Resource $resource, Service $service, CarbonImmutable $time)
    {
        /** @var CreateBooking $action */
        $action = app(CreateBooking::class);
        $action->createBooking(
            name: 'test',
            resourceId: $resource->id,
            requestedDate: $time,
            service: $service,
            locationId: $resource->location->id
        );
    }

    private function createAuthHeader($apiKey): array
    {
        return [
            'Authorization' => 'Bearer ' . $apiKey,
        ];
    }
}
