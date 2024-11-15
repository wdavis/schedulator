<?php

namespace Tests\Feature\Controllers\Api;

use App\Models\Booking;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\Helpers\CreatesTestAccounts;
use Tests\TestCase;

class FirstAvailabilityControllerTest extends TestCase
{
    use RefreshDatabase;
    use CreatesTestAccounts;

    public function test_gets_first_available_resource(): void
    {
        $date = CarbonImmutable::create(2024, 11, 11); // monday
        $this->travelTo($date);

        $accountInfo = $this->createAccount();

        $this->createSchedule(
            day: 'monday',
            startTime: '09:00',
            endTime: '17:00',
            resource: $accountInfo['prodResource'],
        );

        $response = $this->postJson(route('first-availability'), [
            'serviceId' => $accountInfo['prodService']->id,
            'resourceIds' => [
                $accountInfo['prodResource']->id
            ],
            'time' => $date->setTimeFromTimeString('10:00')->toIso8601String(),
        ], headers: $this->createAuthHeader($accountInfo['prodApiKey']));

        $response->assertOk();

        $response->assertJsonStructure([
            'id',
            'name',
            'active',
            // todo add booking_window_end_override
            // todo add cancellation_window_end_override
            'booking_window_lead_override',
            'meta'
        ]);

        $this->assertEquals($accountInfo['prodResource']->id, $response->json('id'));

    }

    public function test_inactive_resource_is_not_returned(): void
    {
        $date = CarbonImmutable::create(2024, 11, 11); // monday
        $this->travelTo($date);

        $accountInfo = $this->createAccount();

        $inactiveResource = $this->createResource(
            name: 'Inactive Resource',
            environmentId: $accountInfo['prodEnvironmentId'],
            active: false
        );

        $this->createSchedule(
            day: 'monday',
            startTime: '09:00',
            endTime: '17:00',
            resource: $inactiveResource,
        );

        $response = $this->postJson(route('first-availability'), [
            'serviceId' => $accountInfo['prodService']->id,
            'resourceIds' => [
                $inactiveResource->id
            ],
            'time' => $date->setTimeFromTimeString('13:00')->toIso8601String(),
        ], headers: $this->createAuthHeader($accountInfo['prodApiKey']));

        $response->assertStatus(404);

        $this->assertStringContainsString('No time slots available', $response->json('message'));
    }

    public function test_order_of_resource_ids_determines_order_of_availability(): void
    {
        $date = CarbonImmutable::create(2024, 11, 11); // monday
        $this->travelTo($date);

        $accountInfo = $this->createAccount();

        $this->createSchedule(
            day: 'monday',
            startTime: '10:00',
            endTime: '10:15',
            resource: $accountInfo['prodResource'],
        );

        $anotherResource = $this->createResource(
            name: 'Another Resource',
            environmentId: $accountInfo['prodEnvironmentId'],
        );

        $this->createSchedule(
            day: 'monday',
            startTime: '10:00',
            endTime: '10:15',
            resource: $anotherResource,
        );

        $response = $this->postJson(route('first-availability'), [
            'serviceId' => $accountInfo['prodService']->id,
            'resourceIds' => [
                $anotherResource->id,
                $accountInfo['prodResource']->id
            ],
            'time' => $date->setTimeFromTimeString('10:00')->toIso8601String(),
        ], headers: $this->createAuthHeader($accountInfo['prodApiKey']));

        $this->assertEquals($anotherResource->id, $response->json('id'));
    }

    public function test_resource_lead_time_is_honored()
    {
        $date = CarbonImmutable::create(2024, 11, 11, 8, 59); // monday 9 am
        $this->travelTo($date);

        $accountInfo = $this->createAccount();

        $this->createSchedule(
            day: 'monday',
            startTime: '09:00',
            endTime: '17:00',
            resource: $accountInfo['prodResource'],
        );

        // the first resource has a 45 minute lead time
        // todo change this to booking_window_end_override
        $accountInfo['prodResource']->booking_window_lead_override = 45;
        $accountInfo['prodResource']->save();

        $winningResource = $this->createResource(
            name: 'Winning Resource',
            environmentId: $accountInfo['prodEnvironmentId'],
        );

        $this->createSchedule(
            day: 'monday',
            startTime: '09:00',
            endTime: '17:00',
            resource: $winningResource,
        );

        // the second resource has a 0 minute lead time, so they should be the first
        // todo change this to booking_window_end_override
        $winningResource->booking_window_lead_override = 0;
        $winningResource->save();

        $response = $this->postJson(route('first-availability'), [
            'serviceId' => $accountInfo['prodService']->id,
            'resourceIds' => [
                $accountInfo['prodResource']->id,
                $winningResource->id
            ],
            'time' => $date->setTimeFromTimeString('9:00')->toIso8601String(),
        ], headers: $this->createAuthHeader($accountInfo['prodApiKey']));

        $response->assertOk();

        $this->assertEquals($winningResource->id, $response->json('id'));
    }

    // todo bookings block first available
    public function test_booking_blocks_resource_from_being_first(): void
    {
        $date = CarbonImmutable::create(2024, 11, 11, 8, 59); // monday 9 am
        $this->travelTo($date);

        $accountInfo = $this->createAccount();

        $this->createSchedule(
            day: 'monday',
            startTime: '09:00',
            endTime: '17:00',
            resource: $accountInfo['prodResource'],
        );

        // create a booking so this provider will not be available at the requested time
        $this->createBooking(
            resource: $accountInfo['prodResource'],
            service: $accountInfo['prodService'],
            time: $date->setTimeFromTimeString('9:00')
        );

        // todo change this to booking_window_end_override
        $accountInfo['prodResource']->booking_window_lead_override = 0;
        $accountInfo['prodResource']->save();

        $winningResource = $this->createResource(
            name: 'Winning Resource',
            environmentId: $accountInfo['prodEnvironmentId'],
        );

        $this->createSchedule(
            day: 'monday',
            startTime: '09:00',
            endTime: '17:00',
            resource: $winningResource,
        );

        // todo change this to booking_window_end_override
        $winningResource->booking_window_lead_override = 0;
        $winningResource->save();

        $response = $this->postJson(route('first-availability'), [
            'serviceId' => $accountInfo['prodService']->id,
            'resourceIds' => [
                $accountInfo['prodResource']->id,
                $winningResource->id
            ],
            'time' => $date->setTimeFromTimeString('9:00')->toIso8601String(),
        ], headers: $this->createAuthHeader($accountInfo['prodApiKey']));

        $response->assertOk();

        // prodResource has a booking at the requested time, so winningResource should be first
        $this->assertEquals($winningResource->id, $response->json('id'));
    }

}
