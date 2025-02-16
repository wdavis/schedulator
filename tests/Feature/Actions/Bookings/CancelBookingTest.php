<?php

namespace Tests\Feature\Actions\Bookings;

use App\Actions\Bookings\CancelBooking;
use App\Models\Booking;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancelBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_can_be_cancelled(): void
    {
        $booking = Booking::factory()->create();
        $cancelBooking = new CancelBooking;
        $cancelledBooking = $cancelBooking->cancel($booking);

        $this->assertNotNull($cancelledBooking->cancelled_at);
    }

    public function test_booking_cannot_be_cancelled_twice(): void
    {
        $booking = Booking::factory()->create(['cancelled_at' => now()]);
        $cancelBooking = new CancelBooking;

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Booking has already been cancelled');

        $cancelBooking->cancel($booking);
    }

    public function test_booking_cannot_be_cancelled_if_past_cancellation_lead_time(): void
    {
        $service = Service::factory()->create(['cancellation_window_end' => 60]);
        $booking = Booking::factory()->create(['service_id' => $service->id, 'starts_at' => Carbon::now()->addMinutes(30)]);

        $cancelBooking = new CancelBooking;

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Booking cannot be cancelled');

        $cancelBooking->cancel($booking);
    }

    public function test_booking_can_be_cancelled_forcefully_past_cancellation_lead_time(): void
    {
        $service = Service::factory()->create(['cancellation_window_end' => 60]);
        $booking = Booking::factory()->create(['service_id' => $service->id, 'starts_at' => Carbon::now()->addMinutes(30)]);

        $cancelBooking = new CancelBooking;
        $cancelledBooking = $cancelBooking->cancel($booking, true);

        $this->assertNotNull($cancelledBooking->cancelled_at);
    }

    public function test_resource_cancellation_window_override_is_honored(): void
    {
        $date = Carbon::create(2024, 11, 11, 9, 59); // 9:59 am
        $this->travelTo($date);

        // Create a service with a 2 hour cancellation window
        $service = Service::factory()->create(['cancellation_window_end' => 120]);
        // Create a booking at 11:00 am
        $booking = Booking::factory()->create(['service_id' => $service->id, 'starts_at' => $date->setTimeFromTimeString('11:00')]);
        // Create a resource with a 1 hour cancellation window override
        $booking->resource->cancellation_window_end_override = 60;
        $booking->resource->save();

        $cancelBooking = new CancelBooking;
        $cancelledBooking = $cancelBooking->cancel($booking);

        // The booking should be cancelled because the resource's override is 1 hour
        $this->assertNotNull($cancelledBooking->cancelled_at);
    }
}
