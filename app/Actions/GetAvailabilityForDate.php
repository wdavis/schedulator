<?php

namespace App\Actions;

use App\Models\Resource;
use Carbon\CarbonImmutable;
use Spatie\Period\PeriodCollection;

class GetAvailabilityForDate
{
    protected GetCombinedScheduleForDate $getCombinedScheduleForDate;
    protected GetBookings $getBookings;
    protected BuildBookingPeriods $buildBookingPeriods;

    /**
     * @param GetCombinedScheduleForDate $getCombinedScheduleForDate
     * @param GetBookings $getBookings
     */
    public function __construct(GetCombinedScheduleForDate $getCombinedScheduleForDate, GetBookings $getBookings, BuildBookingPeriods $buildBookingPeriods)
    {
        $this->getCombinedScheduleForDate = $getCombinedScheduleForDate;
        $this->getBookings = $getBookings;
        $this->buildBookingPeriods = $buildBookingPeriods;
    }

    public function get(Resource $resource, CarbonImmutable $timeSlot, string $locationId = null): PeriodCollection
    {
        $date = $timeSlot->startOfDay();
        $endDate = $timeSlot->endOfDay();

        $schedule = $this->getCombinedScheduleForDate->get($resource, startDate: $date, endDate: $endDate);

        $bookings = $this->getBookings->get($resource, startDate: $date, endDate: $endDate, locationId: $locationId);

        if($bookings->isEmpty()) {
            return $schedule;
        }

        $bookingPeriods = $this->buildBookingPeriods->build($bookings);

        return $schedule->subtract($bookingPeriods)->union();
    }
}
