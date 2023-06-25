<?php

namespace App\Actions;

use App\Models\Resource;
use Spatie\Period\Period;

class GetBookingsForDate
{
    public function get(Resource $resource, $locationId, $date)
    {
        // Retrieve the bookings for the given date
        $bookings = $resource->getBookingsForDate($locationId, $date);

        // Create Period objects for each booking
        $bookingPeriods = array_map(function ($booking) {
            return new Period($booking->start_time, $booking->end_time);
        }, $bookings);

        return $bookingPeriods;
    }
}
