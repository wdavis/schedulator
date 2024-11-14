<?php

namespace App\Actions;

use App\Models\Resource;
use App\Models\Service;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Period\PeriodCollection;
use App\Actions\GetSchedulesForDate;
use App\Actions\CombinePeriodCollections;

class GetCombinedSchedulesForDate
{
    private GetSchedulesForDate $getSchedulesForDate;
    private CombinePeriodCollections $combinePeriodCollections;

    public function __construct(
        GetSchedulesForDate $getSchedulesForDate, CombinePeriodCollections $combinePeriodCollections
    ){
        $this->getSchedulesForDate = $getSchedulesForDate;
        $this->combinePeriodCollections = $combinePeriodCollections;
    }

    /**
     * Get availability across all resources for a given date range
     * All the schedules are flattened into a single set of time periods
     * We use this to see availability across all resources, like if you
     * wanted to show a list of available times that could be booked against
     * multiple resources
     *
     * @param Collection<Resource> $resources
     * @param CarbonImmutable $startDate
     * @param CarbonImmutable $endDate
     * @return PeriodCollection
     */
    public function get(Collection $resources, Service $service, CarbonImmutable $startDate, CarbonImmutable $endDate): PeriodCollection
    {
        $schedules = $this->getSchedulesForDate->get($resources, $service, $startDate, $endDate);

        // at this point we have built all the individual schedules
        // now we need to merge them all together by taking each PeriodCollection in $schedules and doing a union
        return $this->combinePeriodCollections->combine($schedules, key: 'periods');
    }
}
