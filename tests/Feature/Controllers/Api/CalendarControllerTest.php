<?php

namespace Tests\Feature\Controllers\Api;

use App\Enums\ScheduleOverrideType;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\CreatesTestAccounts;
use Tests\TestCase;

class CalendarControllerTest extends TestCase
{
    use CreatesTestAccounts;
    use RefreshDatabase;

    public function test_calendar_shows_openings(): void
    {
        $accountInfo = $this->createAccount();

        $this->createSchedule(
            day: 'monday',
            startTime: '08:00',
            endTime: '8:30',
            resource: $accountInfo['prodResource']
        );

        // get date of nearest monday
        $startDate = CarbonImmutable::now()->next('monday');
        $endDate = $startDate->addDays(6);

        $response = $this->postJson(
            uri: route('calendar.index'),
            data: [
                'serviceId' => $accountInfo['prodService']->id,
                'startDate' => $startDate->toIso8601String(),
                'endDate' => $endDate->toIso8601String(),
                'format' => 'days',
            ],
            headers: [
                'Authorization' => 'Bearer '.$accountInfo['prodApiKey'],
            ]
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
            'date' => $startDate->toDateString(),
            'day' => 'Mon',
            'slots' => [
                [
                    'bookings' => [],
                    'end' => $startDate->setTimeFromTimeString('08:15')->toIso8601String(),
                    'openings' => [
                        [
                            'end' => $startDate->setTimeFromTimeString('08:15')->toIso8601String(),
                            'start' => $startDate->setTimeFromTimeString('08:00')->toIso8601String(),
                            'type' => 'opening',
                        ],
                    ],
                    'start' => $startDate->setTimeFromTimeString('08:00')->toIso8601String(),
                    'type' => 'opening_slot',
                ],
                [
                    'bookings' => [],
                    'end' => $startDate->setTimeFromTimeString('08:30')->toIso8601String(),
                    'openings' => [
                        [
                            'end' => $startDate->setTimeFromTimeString('08:30')->toIso8601String(),
                            'start' => $startDate->setTimeFromTimeString('08:15')->toIso8601String(),
                            'type' => 'opening',
                        ],
                    ],
                    'start' => $startDate->setTimeFromTimeString('08:15')->toIso8601String(),
                    'type' => 'opening_slot',
                ],
            ],
        ]);
    }

    public function test_calendar_only_shows_openings_from_specific_environment(): void
    {
        $date = CarbonImmutable::create(2024, 1, 1);
        $this->travelTo($date);

        $accountInfo = $this->createAccount();

        $this->createSchedule(
            day: 'monday',
            startTime: '08:00',
            endTime: '8:15',
            resource: $accountInfo['prodResource']
        );

        $this->createSchedule(
            day: 'tuesday',
            startTime: '08:00',
            endTime: '8:15',
            resource: $accountInfo['stagingResource']
        );

        // get date of nearest monday
        $prodDate = $date; // monday
        $stagingDate = $date->addDay()->endOfDay(); // tuesday

        $response = $this->postJson(
            uri: route('calendar.index'),
            data: [
                'serviceId' => $accountInfo['prodService']->id,
                'startDate' => $prodDate->toIso8601String(),
                'endDate' => $stagingDate->toIso8601String(),
                'format' => 'days',
            ],
            headers: [
                'Authorization' => 'Bearer '.$accountInfo['prodApiKey'],
            ]
        );

        $response->assertJsonCount(1, '0.slots');
        $response->assertJsonCount(0, '1.slots');
    }

    public function test_bookings_are_registered(): void
    {
        $accountInfo = $this->createAccount();

        $this->createSchedule(
            day: 'monday',
            startTime: '08:00',
            endTime: '8:30',
            resource: $accountInfo['prodResource']
        );

        $startDate = CarbonImmutable::now()->next('monday');

        $this->createBooking(
            resource: $accountInfo['prodResource'],
            service: $accountInfo['prodService'],
            time: $startDate->setTimeFromTimeString('08:00')
        );

        $response = $this->postJson(
            uri: route('calendar.index'),
            data: [
                'serviceId' => $accountInfo['prodService']->id,
                'startDate' => $startDate->toIso8601String(),
                'endDate' => $startDate->endOfDay()->toIso8601String(),
                'format' => 'days',
            ],
            headers: $this->createAuthHeader($accountInfo['prodApiKey'])
        );

        $response->assertJsonCount(1, '0.slots.0.bookings');
        $response->assertJsonCount(0, '0.slots.0.openings');
        $this->assertEquals('test', $response->json()[0]['slots'][0]['bookings'][0]['record']['name']);

        // check that there is only one opening
        $response->assertJsonCount(1, '0.slots.1.openings');
        $response->assertJsonCount(0, '0.slots.1.bookings');
    }

    public function test_overrides_can_block_out_entire_day(): void
    {
        $accountInfo = $this->createAccount();

        $this->createSchedule(
            day: 'monday',
            startTime: '08:00',
            endTime: '12:00',
            resource: $accountInfo['prodResource']
        );

        $startDate = CarbonImmutable::now()->next('monday');

        $this->createOverride(
            resource: $accountInfo['prodResource'],
            type: ScheduleOverrideType::block,
            startDate: $startDate->setTimeFromTimeString('08:00'),
            endDate: $startDate->setTimeFromTimeString('12:00')
        );

        $response = $this->postJson(
            uri: route('calendar.index'),
            data: [
                'serviceId' => $accountInfo['prodService']->id,
                'startDate' => $startDate->toIso8601String(),
                'endDate' => $startDate->endOfDay()->toIso8601String(),
                'format' => 'days',
            ],
            headers: $this->createAuthHeader($accountInfo['prodApiKey'])
        );

        $response->assertJsonCount(0, '0.slots');
    }

    public function test_override_openings_are_added(): void
    {
        $accountInfo = $this->createAccount(defaultServiceDuration: 60);

        $this->createSchedule(
            day: 'monday',
            startTime: '08:00',
            endTime: '12:00',
            resource: $accountInfo['prodResource']
        );

        $startDate = CarbonImmutable::now()->next('monday');

        $this->createOverride(
            resource: $accountInfo['prodResource'],
            type: ScheduleOverrideType::opening,
            startDate: $startDate->setTimeFromTimeString('13:00'),
            endDate: $startDate->setTimeFromTimeString('17:00')
        );

        $response = $this->postJson(
            uri: route('calendar.index'),
            data: [
                'serviceId' => $accountInfo['prodService']->id,
                'startDate' => $startDate->toIso8601String(),
                'endDate' => $startDate->endOfDay()->toIso8601String(),

            ],
            headers: $this->createAuthHeader($accountInfo['prodApiKey'])
        );

        $response->assertJsonCount(9, '0.slots'); // count including gaps
        $this->assertEquals(8, $this->countSlotsByType($response->json('0.slots'), 'opening_slot'));

    }

    public function test_partial_block_on_schedule(): void
    {
        $date = CarbonImmutable::create(2024, 1, 1);
        $this->travelTo($date);

        $accountInfo = $this->createAccount();

        $this->createSchedule(
            day: 'monday',
            startTime: '08:00',
            endTime: '12:00',
            resource: $accountInfo['prodResource']
        );

        $startDate = $date;

        $this->createOverride(
            resource: $accountInfo['prodResource'],
            type: ScheduleOverrideType::block,
            startDate: $startDate->setTimeFromTimeString('09:00'),
            endDate: $startDate->setTimeFromTimeString('11:00')
        );

        $response = $this->postJson(
            uri: route('calendar.index'),
            data: [
                'serviceId' => $accountInfo['prodService']->id,
                'startDate' => $startDate->toIso8601String(),
                'endDate' => $startDate->endOfDay()->toIso8601String(),
                'format' => 'days',
            ],
            headers: $this->createAuthHeader($accountInfo['prodApiKey'])
        );

        $slots = $response->json('0.slots');
        $this->assertEquals(8, $this->countSlotsByType($slots, 'opening_slot'), 'There should be 8 opening slots');
        $this->assertEquals(1, $this->countSlotsByType($slots, 'gap'), 'There should be 1 gap');
    }

    public function test_pull_specific_resource_ids(): void
    {
        $date = CarbonImmutable::create(2024, 1, 1);
        $this->travelTo($date);

        $accountInfo = $this->createAccount();

        $includedResource = $this->createResource(
            name: 'Included Resource',
            environmentId: $accountInfo['prodEnvironmentId']
        );

        $this->createSchedule(
            day: 'monday',
            startTime: '08:00',
            endTime: '12:00',
            resource: $includedResource
        );

        $includedResource2 = $this->createResource(
            name: 'Included Resource 2',
            environmentId: $accountInfo['prodEnvironmentId']
        );

        $this->createSchedule(
            day: 'monday',
            startTime: '12:00',
            endTime: '14:00',
            resource: $includedResource2
        );

        $excludedResource = $this->createResource(
            name: 'Excluded Resource',
            environmentId: $accountInfo['prodEnvironmentId']
        );

        $this->createSchedule(
            day: 'monday',
            startTime: '14:00',
            endTime: '16:00',
            resource: $excludedResource
        );

        $startDate = $date;
        $endDate = $startDate->endOfDay();

        $response = $this->postJson(
            uri: route('calendar.index'),
            data: [
                'serviceId' => $accountInfo['prodService']->id,
                'startDate' => $startDate->toIso8601String(),
                'endDate' => $endDate->toIso8601String(),
                'format' => 'days',
                'resourceIds' => [
                    $includedResource->id,
                    $includedResource2->id,
                ],
            ],
            headers: $this->createAuthHeader($accountInfo['prodApiKey'])
        );

        $slots = $response->json('0.slots');
        $this->assertEquals(24, $this->countSlotsByType($slots, 'opening_slot'), 'There should be 2 opening slots');

        // last slot should be 13:45 - 14:00, 14:00 - 16:00 that would be the excluded resource
        $this->assertEquals($startDate->setTimeFromTimeString('13:45')->toIso8601String(), $slots[23]['start']);
    }
}
