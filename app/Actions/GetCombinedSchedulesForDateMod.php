<?php

namespace App\Actions;

use App\Models\Resource;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Period\Boundaries;
use Spatie\Period\Period;
use Spatie\Period\PeriodCollection;
use Spatie\Period\Precision;

class GetCombinedSchedulesForDateMod
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
     * @return int
     */
    public function get(Collection $resources, CarbonImmutable $startDate, CarbonImmutable $endDate): int
    {
        // for now just load all the resources locations and schedules
        $resources->load('locations.schedules');

        // todo possible preloads for all the resources

        // Initialize total count of appointments
        $totalAppointmentCount = 0;

        // Iterate through each resource
        foreach ($resources as $resource) {
            // Get the schedules, overrides, and existing bookings for this resource only
            $schedules = $resource->locations->pluck('schedules')->flatten();
            $availableOverrides = $this->getScheduleOverrides->get([$resource->id], $startDate, $endDate);
            $overrides = $this->buildScheduleOverrides->get($availableOverrides);
            $recurring = $this->buildRecurringSchedule->build($schedules, $startDate, $endDate);
//            $existingBookings = $this->getBookings->get([$resource->id], $startDate, $endDate);

            // Generate the final PeriodCollection
            $periods = $recurring
                ->add(...$overrides['opening']) // merge in the openings
//                ->subtract(...$existingBookings) // subtract the existing bookings
                ->union() // merge the overlapping periods
                ->subtract($overrides['block']); // subtract out the schedule blocks

            // Count the number of appointment slots in each period and add to the total
            foreach ($periods as $period) {
                // Convert start and end times to minutes since midnight
                $startTimeMinutes = ($period->start()->format('H') * 60) + $period->start()->format('i');
                $endTimeMinutes = ($period->end()->format('H') * 60) + $period->end()->format('i');

                // Adjust start and end times to the next and last standard appointment time, respectively
                $adjustedStartTimeMinutes = (int)ceil($startTimeMinutes / 15) * 15;
                $adjustedEndTimeMinutes = (int)floor($endTimeMinutes / 15) * 15;

                // Calculate the length of the adjusted period
                $adjustedLengthMinutes = max(0, $adjustedEndTimeMinutes - $adjustedStartTimeMinutes);

                // Count the number of appointments that can fit into the adjusted period
                $totalAppointmentCount += intdiv($adjustedLengthMinutes, 15); // appointment duration is fixed at 15 minutes
            }
        }
        return $totalAppointmentCount;
    }
}
