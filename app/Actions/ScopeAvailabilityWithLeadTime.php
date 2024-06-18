<?php

namespace App\Actions;

use Carbon\CarbonImmutable;
use Spatie\Period\Boundaries;
use Spatie\Period\Period;
use Spatie\Period\PeriodCollection;
use Spatie\Period\Precision;

class ScopeAvailabilityWithLeadTime
{
    private AdjustTimeInterval $adjustTimeInterval;

    public function __construct(AdjustTimeInterval $adjustTimeInterval)
    {
        $this->adjustTimeInterval = $adjustTimeInterval;
    }

    public function scope(
        PeriodCollection $availability,
        int $leadTimeInMinutes,
        int $bookingDurationInMinutes,
        ?CarbonImmutable $requestedStartDate = null
    ): PeriodCollection
    {
        if(!$requestedStartDate) {
            $requestedStartDate = CarbonImmutable::now();
        }

        $requestedDate = $this->adjustTimeInterval->adjust($requestedStartDate->second(0)->millis(0), $bookingDurationInMinutes);

        $period = new Period($requestedDate->subYears(5), $requestedDate->addMinutes($leadTimeInMinutes), Precision::MINUTE(), Boundaries::EXCLUDE_ALL());

        $subtractedPeriod = $availability->subtract($period);

        return $subtractedPeriod;
    }
}
