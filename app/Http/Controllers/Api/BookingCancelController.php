<?php

namespace App\Http\Controllers\Api;

use App\Actions\Bookings\CancelBooking;
use App\Models\Booking;
use App\Traits\InteractsWithEnvironment;

class BookingCancelController
{
    use InteractsWithEnvironment;

    public function __construct(private CancelBooking $cancelBooking)
    {
    }

    public function update(string $id)
    {
        $booking = Booking::whereHas('resource', function ($query) {
            $query->where('environment_id', $this->getApiEnvironmentId());
        })->with('service', 'resource')->where('id', $id)->firstOrFail();

//        try {
        $this->cancelBooking->cancel($booking, request('force', false));

        return response()->json([], 204);
//        } catch () {
//            return response()->json([], 204);
//        }

    }
}
