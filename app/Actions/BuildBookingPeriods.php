<?php

namespace App\Actions;

use App\Models\Booking;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Period\Boundaries;
use Spatie\Period\Period;
use Spatie\Period\PeriodCollection;
use Spatie\Period\Precision;

class BuildBookingPeriods
{
    /**
     * @param Collection<Booking> $bookings
     * @return PeriodCollection
     */
    public function build(Collection $bookings): PeriodCollection
    {
        return new PeriodCollection(...$bookings->map(function ($booking) {
            return new Period(
                CarbonImmutable::parse($booking->starts_at),
                CarbonImmutable::parse($booking->ends_at),
                precision: Precision::MINUTE(),
//                boundaries: Boundaries::EXCLUDE_NONE() // this allows the periods to be merged without an offset
                boundaries: Boundaries::EXCLUDE_ALL() // this allows the periods to be merged without an offset
            );
        }));
    }
}
