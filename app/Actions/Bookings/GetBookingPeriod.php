<?php

namespace App\Actions\Bookings;

use App\Models\Booking;
use App\Models\Service;
use Carbon\CarbonImmutable;
use Spatie\Period\Boundaries;
use Spatie\Period\Period;
use Spatie\Period\Precision;

class GetBookingPeriod
{
    public function get(Booking $booking, Service $service): array
    {
        if ($booking->service_id !== $service->id) {
            throw new \Exception('Booking does not match the service');
        }

        return array_merge(
            $booking->toArray(),
            [
                'duration' => $service->duration,
            ],
            [
                'period' => new Period(
                    CarbonImmutable::parse($booking->starts_at),
                    CarbonImmutable::parse($booking->ends_at),
                    precision: Precision::MINUTE(),
                    boundaries: Boundaries::EXCLUDE_ALL() // this allows the periods to be merged without an offset
                ),
            ]
        );

        return new Period(
            CarbonImmutable::parse($booking->starts_at),
            CarbonImmutable::parse($booking->ends_at),
            precision: Precision::MINUTE(),
            boundaries: Boundaries::EXCLUDE_ALL() // this allows the periods to be merged without an offset
        );
    }
}
