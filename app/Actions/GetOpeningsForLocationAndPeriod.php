<?php

namespace App\Actions;

use App\Models\Resource;
use App\Models\Location;
use Spatie\Period\Boundaries;
use Spatie\Period\Period;
use Spatie\Period\Precision;

class GetOpeningsForLocationAndPeriod
{
    protected GetCombinedScheduleForDate $getCombinedScheduleForDate;
    protected GetBookingsForDate $getBookingsForDate;

    public function __construct(GetCombinedScheduleForDate $getCombinedScheduleForDate, GetBookingsForDate $getBookingsForDate)
    {
        $this->getCombinedScheduleForDate = $getCombinedScheduleForDate;
        $this->getBookingsForDate = $getBookingsForDate;
    }

    public function get(Resource $resource, Period $searchPeriod)
    {
        $openings = [];

        // Iterate through each day in the search period
        foreach ($searchPeriod as $date) {
            // Get the combined schedule for the resource on the given date
            $combinedPeriods = $this->getCombinedScheduleForDate->get($resource, $date);

            // Get the bookings for the resource on the given date
            $bookingPeriods = $this->getBookingsForDate->get($resource, $location->id, $date);

            // Check for openings in the combined schedule
            foreach ($combinedPeriods as $period) {
                if (!$this->isPeriodBooked($period, $bookingPeriods)) {
                    $openings[] = $period->overlap(new Period($searchPeriod->start(), $searchPeriod->end(), Precision::MINUTE(), Boundaries::EXCLUDE_NONE()))->toIso8601();
                }
            }
        }

        return $openings;
    }
}
