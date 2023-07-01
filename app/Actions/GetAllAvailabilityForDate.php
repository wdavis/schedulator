<?php

namespace App\Actions;

use App\Models\Resource;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Period\Period;
use Spatie\Period\PeriodCollection;
use Spatie\Period\Precision;

class GetAllAvailabilityForDate
{
    protected GetCombinedSchedulesForDate $getCombinedScheduleForDate;
    protected GetAllBookings $getBookings;
    protected BuildBookingPeriods $buildBookingPeriods;

    /**
     * @param GetCombinedSchedulesForDate $getCombinedScheduleForDate
     * @param GetAllBookings $getBookings
     * @param BuildBookingPeriods $buildBookingPeriods
     */
    public function __construct(GetCombinedSchedulesForDate $getCombinedScheduleForDate, GetAllBookings $getBookings, BuildBookingPeriods $buildBookingPeriods)
    {
        $this->getCombinedScheduleForDate = $getCombinedScheduleForDate;
        $this->getBookings = $getBookings;
        $this->buildBookingPeriods = $buildBookingPeriods;
    }

    /**
     * @param Collection<Resource> $resources
     * @param CarbonImmutable $startDate
     * @param string|null $locationId
     * @return PeriodCollection
     */
    public function get(Collection $resources, CarbonImmutable $startDate, string $locationId = null, ?CarbonImmutable $endDate = null): PeriodCollection
    {
        $date = $startDate;
        if(!$endDate) {
            $endDate = $startDate->endOfDay();
        }

        $schedule = $this->getCombinedScheduleForDate->get($resources, startDate: $date, endDate: $endDate);

        $bookings = $this->getBookings->get($resources, startDate: $date, endDate: $endDate, locationId: $locationId);

        if($bookings->isEmpty()) {
            return $schedule; // todo why are we doing this?
        }

        $bookingPeriods = $this->buildBookingPeriods->build($bookings);

        return $schedule->subtract($bookingPeriods)
            ->union();
    }
}
