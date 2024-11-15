<?php

namespace Tests\Feature\Controllers\Api;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\Helpers\CreatesTestAccounts;
use Tests\TestCase;

class CalendarControllerTest extends TestCase
{
    use RefreshDatabase;
    use CreatesTestAccounts;

    public function test_calendar_shows_openings_for_environment(): void
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
                'Authorization' => 'Bearer ' . $accountInfo['prodApiKey'],
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
                    ]
                ]
            ]
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
                ]
            ]
        ]);
    }

    public function test_calendar_only_shows_openings_from_environment(): void
    {
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
        $prodDate = CarbonImmutable::now()->next('monday');
        $stagingDate = CarbonImmutable::now()->next('tuesday')->endOfDay();

        $response = $this->postJson(
            uri: route('calendar.index'),
            data: [
                'serviceId' => $accountInfo['prodService']->id,
                'startDate' => $prodDate->toIso8601String(),
                'endDate' => $stagingDate->toIso8601String(),
                'format' => 'days',
            ],
            headers: [
                'Authorization' => 'Bearer ' . $accountInfo['prodApiKey'],
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
        $this->assertEquals('test', $response->json()[0]['slots'][0]['bookings'][0]['record']['name']);
    }

    // todo test overrides
    // todo disabled resources
    // todo specific resource ids

}
