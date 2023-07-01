<?php

namespace App\Actions;

use App\Models\Resource;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Period\Boundaries;
use Spatie\Period\Period;
use Spatie\Period\PeriodCollection;
use Spatie\Period\Precision;

class GetCombinedSchedulesForDate
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

    /**
     * @param Collection<Resource> $resources
     * @param CarbonImmutable $startDate
     * @param CarbonImmutable $endDate
     * @return PeriodCollection
     */
    public function get(Collection $resources, CarbonImmutable $startDate, CarbonImmutable $endDate): PeriodCollection
    {
        // for now just load all the resources locations and schedules
        $resources->load('locations.schedules');

        // extract all the schedules from the resources
        $schedules = $resources->pluck('locations')->flatten()->pluck('schedules')->flatten();

        // get all the resource id's
        $resourceIds = $resources->pluck('id')->toArray();

        // Retrieve the schedule overrides for the given date
        $availableOverrides = $this->getScheduleOverrides->get($resourceIds, $startDate, $endDate);

        // build overrides for the date
        $overrides = $this->buildScheduleOverrides->get($availableOverrides);

        // Build the recurring schedule for the given date
        $recurring = $this->buildRecurringSchedule->build($schedules, $startDate, $endDate);

        return $recurring
            ->add(...$overrides['opening']) // merge in the openings
            ->union() // merge the overlapping periods
            ->subtract($overrides['block']); // subtract out the schedule blocks
    }
}
