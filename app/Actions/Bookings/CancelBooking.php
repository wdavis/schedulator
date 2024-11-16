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

        // todo check if resource is loaded
        if(!$booking->relationLoaded('service')) {
            $booking->load('service');
        }

        // look at the service and check the lead time for cancellation
        /** @var Carbon $startsAt */
        $startsAt = $booking->starts_at;
        $bookingWithCancellationLead = $startsAt->subMinutes($booking->service->cancellation_window_end);

        if($bookingWithCancellationLead->isPast() && !$force) {
            $formattedDuration = $this->formatDuration($booking->service->cancellation_window_end);
            throw new \Exception("Booking cannot be cancelled within {$formattedDuration} of the start time {$booking->starts_at->toIso8601String()}");
        }

        $booking->cancelled_at = now();
        $booking->save();

        return $booking;
    }

    private function formatDuration(?int $minutes): string
    {
        // Handle null or 0 values
        if ($minutes === null || $minutes === 0) {
            return '0 minutes';
        }

        // Calculate hours and remaining minutes
        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        // Format the output based on the hours and minutes
        if ($hours > 0 && $remainingMinutes > 0) {
            return "{$hours} hours {$remainingMinutes} minutes";
        } elseif ($hours > 0) {
            return "{$hours} hours";
        } else {
            return "{$remainingMinutes} minutes";
        }
    }
}
