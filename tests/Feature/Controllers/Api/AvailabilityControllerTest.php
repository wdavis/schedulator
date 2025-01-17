<?php

namespace Tests\Feature\Controllers\Api;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\CreatesTestAccounts;
use Tests\TestCase;

class AvailabilityControllerTest extends TestCase
{
    use CreatesTestAccounts;
    use RefreshDatabase;

    public function test_availability_scopes_to_environment(): void
    {
        $date = CarbonImmutable::create(2024, 11, 10); // sunday
        $this->travelTo($date);

        $accountInfo = $this->createAccount();

        $this->createSchedule(
            day: 'monday',
            startTime: '10:00',
            endTime: '10:30',
            resource: $accountInfo['prodResource']
        );

        $this->createSchedule(
            day: 'tuesday',
            startTime: '10:00',
            endTime: '10:30',
            resource: $accountInfo['stagingResource']
        );

        $startDate = $date->addDay(); // monday 11th
        $wrongEnvironmentDate = $startDate->addDay(); // tuesday 12th

        $response = $this->postJson(
            uri: route('availability'),
            data: [
                'serviceId' => $accountInfo['prodService']->id,
                'startDate' => $date->addDay()->toIso8601String(),
                'endDate' => $date->addDays(2)->endOfDay()->toIso8601String(),
            ],
            headers: $this->createAuthHeader($accountInfo['prodApiKey'])
        );

        $response->assertStatus(200);

        $body = $response->json();

        $response->assertJsonCount(2);

        // assert there are no tuesday schedules
        foreach ($body as $date => $schedules) {
            $this->assertStringNotContainsString($wrongEnvironmentDate->format('Y-m-d'), $schedules['start']);
        }
    }

    public function test_list_availability(): void
    {
        $date = CarbonImmutable::create(2024, 11, 10); // sunday
        $this->travelTo($date);

        $accountInfo = $this->createAccount();

        $this->createSchedule(
            day: 'monday',
            startTime: '10:00',
            endTime: '10:30',
            resource: $accountInfo['prodResource']
        );

        $startDate = $date->addDay(); // monday

        $response = $this->postJson(
            uri: route('availability'),
            data: [
                'serviceId' => $accountInfo['prodService']->id,
                'startDate' => $date->addDay()->toIso8601String(),
                'endDate' => $date->addDay()->endOfDay()->toIso8601String(),
            ],
            headers: $this->createAuthHeader($accountInfo['prodApiKey'])
        );

        $response->assertStatus(200);

        $response->assertJsonStructure([
            '*' => [
                'start',
                'end',
            ],
        ]);
        $response->assertJsonFragment([
            [
                'start' => $startDate->setTimeFromTimeString('10:00')->toIso8601String(),
                'end' => $startDate->setTimeFromTimeString('10:15')->toIso8601String(),
            ],

        ]);
        $response->assertJsonFragment([
            [
                'start' => $startDate->setTimeFromTimeString('10:15')->toIso8601String(),
                'end' => $startDate->setTimeFromTimeString('10:30')->toIso8601String(),
            ],
        ]);
    }

    public function test_days_format(): void
    {
        $date = CarbonImmutable::create(2024, 11, 10); // sunday
        $this->travelTo($date);

        $accountInfo = $this->createAccount();

        $this->createSchedule(
            day: 'monday',
            startTime: '10:00',
            endTime: '10:30',
            resource: $accountInfo['prodResource']
        );

        $startDate = $date->addDay(); // monday

        $response = $this->postJson(
            uri: route('availability'),
            data: [
                'serviceId' => $accountInfo['prodService']->id,
                'startDate' => $date->addDay()->toIso8601String(),
                'endDate' => $date->addDay()->endOfDay()->toIso8601String(),
                'format' => 'days',
            ],
            headers: $this->createAuthHeader($accountInfo['prodApiKey'])
        );

        $response->assertStatus(200);

        $response->assertJsonStructure([
            '*' => [
                'date',
                'day',
                'slots' => [
                    '*' => [
                        'start',
                        'end',
                    ],
                ],
            ],
        ]);

        $response->assertJsonFragment([
            [
                'start' => $startDate->setTimeFromTimeString('10:00')->toIso8601String(),
                'end' => $startDate->setTimeFromTimeString('10:15')->toIso8601String(),
            ],

        ]);

        $response->assertJsonFragment([
            [
                'start' => $startDate->setTimeFromTimeString('10:15')->toIso8601String(),
                'end' => $startDate->setTimeFromTimeString('10:30')->toIso8601String(),
            ],
        ]);

    }

    public function test_disabled_resource_is_not_included_in_schedule(): void
    {
        $date = CarbonImmutable::create(2024, 11, 11); // sunday
        $this->travelTo($date);

        $accountInfo = $this->createAccount();

        $this->createSchedule(
            day: 'monday',
            startTime: '10:00',
            endTime: '10:30',
            resource: $accountInfo['prodResource']
        );

        $inactiveResource = $this->createResource(
            name: 'Inactive Resource',
            environmentId: $accountInfo['prodEnvironmentId'],
            active: false
        );

        $this->createSchedule(
            day: 'monday',
            startTime: '12:00',
            endTime: '12:30',
            resource: $inactiveResource
        );

        $response = $this->postJson(
            uri: route('availability'),
            data: [
                'serviceId' => $accountInfo['prodService']->id,
                'startDate' => $date->toIso8601String(),
                'endDate' => $date->endOfDay()->toIso8601String(),
            ],
            headers: $this->createAuthHeader($accountInfo['prodApiKey'])
        );

        $response->assertStatus(200);
        $response->assertJsonCount(2);

        $response->assertJsonFragment([
            [
                'start' => $date->setTimeFromTimeString('10:00')->toIso8601String(),
                'end' => $date->setTimeFromTimeString('10:15')->toIso8601String(),
            ],
        ]);

        $response->assertJsonFragment([
            [
                'start' => $date->setTimeFromTimeString('10:15')->toIso8601String(),
                'end' => $date->setTimeFromTimeString('10:30')->toIso8601String(),
            ],
        ]);

        $response->assertJsonMissing([
            [
                'start' => $date->setTimeFromTimeString('12:00')->toIso8601String(),
                'end' => $date->setTimeFromTimeString('12:15')->toIso8601String(),
            ],
        ]);

        $response->assertJsonMissing([
            [
                'start' => $date->setTimeFromTimeString('12:15')->toIso8601String(),
                'end' => $date->setTimeFromTimeString('12:30')->toIso8601String(),
            ],
        ]);

    }

    public function test_service_booking_end_time_is_honored(): void
    {
        $date = CarbonImmutable::create(2024, 11, 11, 9, 59); // monday
        $this->travelTo($date);

        $accountInfo = $this->createAccount();

        $this->createSchedule(
            day: 'monday',
            startTime: '10:00',
            endTime: '11:30',
            resource: $accountInfo['prodResource']
        );

        // modify lead time for the service
        $accountInfo['prodService']->booking_window_end = 60;
        $accountInfo['prodService']->save();

        $response = $this->postJson(
            uri: route('availability'),
            data: [
                'serviceId' => $accountInfo['prodService']->id,
                'startDate' => $date->toIso8601String(),
                'endDate' => $date->endOfDay()->toIso8601String(),
            ],
            headers: $this->createAuthHeader($accountInfo['prodApiKey'])
        );

        $response->assertStatus(200);
        $response->assertJsonCount(2);

        $response->assertJsonFragment([
            [
                'start' => $date->setTimeFromTimeString('11:00')->toIso8601String(),
                'end' => $date->setTimeFromTimeString('11:15')->toIso8601String(),
            ],
        ]);

        $response->assertJsonFragment([
            [
                'start' => $date->setTimeFromTimeString('11:15')->toIso8601String(),
                'end' => $date->setTimeFromTimeString('11:30')->toIso8601String(),
            ],
        ]);
    }

    public function test_resource_booking_end_override_is_honored(): void
    {
        $date = CarbonImmutable::create(2024, 11, 11, 9, 59); // monday
        $this->travelTo($date);

        $accountInfo = $this->createAccount();

        $this->createSchedule(
            day: 'monday',
            startTime: '10:00',
            endTime: '12:30',
            resource: $accountInfo['prodResource']
        );

        $accountInfo['prodResource']->booking_window_end_override = 120;
        $accountInfo['prodResource']->save();

        $response = $this->postJson(
            uri: route('availability'),
            data: [
                'serviceId' => $accountInfo['prodService']->id,
                'startDate' => $date->toIso8601String(),
                'endDate' => $date->endOfDay()->toIso8601String(),
            ],
            headers: $this->createAuthHeader($accountInfo['prodApiKey'])
        );

        $response->assertStatus(200);
        $response->assertJsonCount(2);

        $response->assertJsonFragment([
            [
                'start' => $date->setTimeFromTimeString('12:00')->toIso8601String(),
                'end' => $date->setTimeFromTimeString('12:15')->toIso8601String(),
            ],
        ]);

        $response->assertJsonFragment([
            [
                'start' => $date->setTimeFromTimeString('12:15')->toIso8601String(),
                'end' => $date->setTimeFromTimeString('12:30')->toIso8601String(),
            ],
        ]);
    }
}
