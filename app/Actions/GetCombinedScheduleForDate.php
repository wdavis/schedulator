<?php

namespace App\Actions;

use App\Models\Resource;
use Carbon\CarbonImmutable;
use Spatie\Period\Boundaries;
use Spatie\Period\Period;
use Spatie\Period\PeriodCollection;
use Spatie\Period\Precision;

class GetCombinedScheduleForDate
{
    private GetScheduleOverrides $getScheduleOverrides;
    private BuildScheduleOverrides $buildScheduleOverrides;
    private BuildRecurringSchedule $buildRecurringSchedule;

    /**
     * @param GetScheduleOverrides $getScheduleOverrides
     * @param BuildScheduleOverrides $buildScheduleOverrides
     * @param BuildRecurringSchedule $buildRecurringSchedule
     */
    public function __construct(
        GetScheduleOverrides $getScheduleOverrides,
        BuildScheduleOverrides $buildScheduleOverrides,
        BuildRecurringSchedule $buildRecurringSchedule,
    ){
        $this->getScheduleOverrides = $getScheduleOverrides;
        $this->buildScheduleOverrides = $buildScheduleOverrides;
        $this->buildRecurringSchedule = $buildRecurringSchedule;
    }

    public function get(Resource $resource, CarbonImmutable $startDate, CarbonImmutable $endDate): PeriodCollection
    {
        // for now just load all the resources locations and schedules
        $resource->load('locations.schedules');

        //
        $schedules = $resource->locations->pluck('schedules')->flatten();

        // Retrieve the schedule overrides for the given date
        $availableOverrides = $this->getScheduleOverrides->get([$resource->id], $startDate, $endDate);

        // build overrides for the date
        $overrides = $this->buildScheduleOverrides->get($availableOverrides);

        // Retrieve the recurring schedule for the given date
        // we should be getting a period collection here
        $recurring = $this->buildRecurringSchedule->build($schedules, $startDate, $endDate);

        return $recurring
            ->add(...$overrides['opening']) // merge in the openings
            ->union() // merge the overlapping periods
            ->subtract($overrides['block']); // subtract out the schedule blocks
    }
}
