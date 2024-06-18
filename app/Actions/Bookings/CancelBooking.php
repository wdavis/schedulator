<?php

namespace App\Actions\Bookings;

use App\Exceptions\BookingAlreadyCancelledException;
use App\Models\Booking;
use Carbon\Carbon;

class CancelBooking
{
    public function cancel(Booking $booking, bool $force = false): Booking
    {
        if($booking->cancelled_at) {
            throw new BookingAlreadyCancelledException('Booking has already been cancelled');
        }

        if(!$booking->relationLoaded('service')) {
            $booking->load('service');
        }

        // look at the service and check the lead time for cancellation
        /** @var Carbon $startsAt */
        $startsAt = $booking->starts_at;
        $bookingWithCancellationLead = $startsAt->subMinutes($booking->service->cancellation_lead);

        if($bookingWithCancellationLead->isPast() && !$force) {
            throw new \Exception("Booking cannot be cancelled within {$booking->service->cancellation_lead} minutes of the start time {$booking->starts_at}");
        }

        $booking->cancelled_at = now();
        $booking->save();

        return $booking;
    }
}
