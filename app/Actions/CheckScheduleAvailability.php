<?php

namespace App\Actions;

use Carbon\CarbonImmutable;
use Spatie\Period\Period;
use Spatie\Period\PeriodCollection;
use Spatie\Period\Precision;

class CheckScheduleAvailability
{
    public function check(PeriodCollection $openAvailability, CarbonImmutable $requestedStartTime, int $duration = 15, int $bufferBefore = 0): bool
    {
        $requestedEndTime = $requestedStartTime->addMinutes($duration);

        // Buffer would be like a "prep time" before the appointment
        //        if($bufferBefore > 0) {
        //            $requestedStartTime = $requestedStartTime->addMinutes($bufferBefore);
        //        }

        $requestedPeriod = Period::make($requestedStartTime, $requestedEndTime, Precision::MINUTE());

        foreach ($openAvailability as $period) {
            if ($period->contains($requestedPeriod)) {
                return true;
            }
        }

        return false;
    }
}
