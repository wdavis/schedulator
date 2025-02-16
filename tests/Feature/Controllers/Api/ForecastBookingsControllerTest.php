<?php

namespace Tests\Feature\Controllers\Api;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\CreatesTestAccounts;
use Tests\TestCase;

class ForecastBookingsControllerTest extends TestCase
{
    use CreatesTestAccounts;
    use RefreshDatabase;

    public function test_example(): void
    {
        $date = CarbonImmutable::create(2024, 12, 1);
        $this->travelTo($date);

        $accountInfo = $this->createAccount();

        // create 10 bookings
        for ($i = 0; $i < 10; $i++) {
            $hour = $i + 8;
            $this->createBooking(
                $accountInfo['prodResource'],
                $accountInfo['prodService'],
                $date->setTimeFromTimeString("{$hour}:00"),
            );
        }

        $response = $this->post(route('reports.bookings.index'), [
            'startDate' => $date->toIso8601String(),
            'endDate' => $date->addDays(6)->toIso8601String(),
            'resourceIds' => [
                $accountInfo['prodResource']->id,
            ],
        ], $this->createAuthHeader($accountInfo['prodApiKey']));

        $response->assertStatus(200);
        $this->assertEquals(10, $response->json('bookings'));
    }
}
