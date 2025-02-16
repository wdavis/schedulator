<?php

namespace App\Actions;

use Carbon\CarbonImmutable;

class AdjustTimeInterval
{
    // bumpToNextInterval is used to determine whether to bump to the next interval or not
    // e.g. if the interval is 15 minutes and the time is 10:00, bumpToNextInterval will bump to 10:15
    public function adjust(CarbonImmutable $time, int $interval, bool $bumpToNextInterval = true): CarbonImmutable
    {
        $newTime = $time->second(0)
            ->millis(0);

        if (! $bumpToNextInterval && $newTime->minute % $interval === 0) {
            return $newTime;
        }

        return $newTime->addMinutes($interval - ($newTime->minute % $interval));
    }
}
