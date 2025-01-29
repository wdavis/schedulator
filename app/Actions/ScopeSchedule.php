<?php

namespace App\Actions;

use Carbon\CarbonImmutable;
use Spatie\Period\Boundaries;
use Spatie\Period\Period;
use Spatie\Period\PeriodCollection;
use Spatie\Period\Precision;

class ScopeSchedule
{
    public function scope(
        PeriodCollection $availability,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
    ): PeriodCollection {
        // remove everything outside of the requested date range

        $beginningScope = new Period($startDate->subYears(5), $startDate, Precision::MINUTE(), Boundaries::EXCLUDE_ALL());
        $availability = $availability->subtract($beginningScope);

        // look at the end date
        $endScope = new Period($endDate->addSecond(), $endDate->addYear(), Precision::MINUTE(), Boundaries::EXCLUDE_ALL());
        $availability = $availability->subtract($endScope);

        return $availability;
    }
}
