<?php

namespace Tests\Feature\Actions\Bookings;

use App\Actions\Bookings\GetBookingPeriod;
use App\Models\Booking;
use App\Models\Service;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Str;
use Spatie\Period\Period;
use Tests\TestCase;

class GetBookingPeriodTest extends TestCase
{
    /** @test */
    public function test_get_booking_period_with_matching_service()
    {
        // Arrange
        $serviceId = (string) Str::uuid();

        $booking = Booking::factory()->make([
            'service_id' => $serviceId,
            'starts_at' => CarbonImmutable::now(),
            'ends_at' => CarbonImmutable::now()->addHour(),
        ]);

        $service = Service::factory()->make([
            'id' => $serviceId,
        ]);

        $action = new GetBookingPeriod();

        // Act
        $period = $action->get($booking, $service);

        // Assert
        $this->assertInstanceOf(Period::class, $period);
        $this->assertEquals(CarbonImmutable::parse($booking->starts_at), $period->start());
        $this->assertEquals(CarbonImmutable::parse($booking->ends_at), $period->end());
    }

    /** @test */
    public function test_get_booking_period_with_non_matching_service()
    {
        // Arrange
        $booking = Booking::factory()->make([
            'service_id' => (string) Str::uuid(),
            'starts_at' => CarbonImmutable::now(),
            'ends_at' => CarbonImmutable::now()->addHour(),
        ]);

        $service = Service::factory()->make([
            'id' => (string) Str::uuid(),
        ]);

        $action = new GetBookingPeriod();

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Booking does not match the service');

        // Act
        $action->get($booking, $service);
    }
}
