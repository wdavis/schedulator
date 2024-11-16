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

    public function test_booking_can_be_cancelled()
    {
        $booking = Booking::factory()->create();
        $cancelBooking = new CancelBooking();
        $cancelledBooking = $cancelBooking->cancel($booking);

        $this->assertNotNull($cancelledBooking->cancelled_at);
    }

    public function test_booking_cannot_be_cancelled_twice()
    {
        $booking = Booking::factory()->create(['cancelled_at' => now()]);
        $cancelBooking = new CancelBooking();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Booking has already been cancelled');

        $cancelBooking->cancel($booking);
    }

    public function test_booking_cannot_be_cancelled_if_past_cancellation_lead_time()
    {
        $service = Service::factory()->create(['cancellation_window_end' => 60]);
        $booking = Booking::factory()->create(['service_id' => $service->id, 'starts_at' => Carbon::now()->addMinutes(30)]);

        $cancelBooking = new CancelBooking();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Booking cannot be cancelled');

        $cancelBooking->cancel($booking);
    }

    public function test_booking_can_be_cancelled_forcefully_past_cancellation_lead_time()
    {
        $service = Service::factory()->create(['cancellation_window_end' => 60]);
        $booking = Booking::factory()->create(['service_id' => $service->id, 'starts_at' => Carbon::now()->addMinutes(30)]);

        $cancelBooking = new CancelBooking();
        $cancelledBooking = $cancelBooking->cancel($booking, true);

        $this->assertNotNull($cancelledBooking->cancelled_at);
    }
}
