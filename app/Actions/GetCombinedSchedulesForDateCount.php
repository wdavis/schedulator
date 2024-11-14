<?php

namespace App\Actions;

use App\Models\Resource;
use App\Models\Service;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use App\Actions\GetSchedulesForDate;

class GetCombinedSchedulesForDateCount
{
    private GetSchedulesForDate $getSchedulesForDate;

    /**
     * @param GetSchedulesForDate $getSchedulesForDate
     */
    public function __construct(GetSchedulesForDate $getSchedulesForDate){
        $this->getSchedulesForDate = $getSchedulesForDate;
    }

    /**
     * This will give you the total number of appointment slots available for a given date range
     *
     * @param Collection<Resource> $resources
     * @param Service $service
     * @param CarbonImmutable $startDate
     * @param CarbonImmutable $endDate
     * @return int
     */
    public function get(Collection $resources, Service $service, CarbonImmutable $startDate, CarbonImmutable $endDate): int
    {
        // for now just load all the resources locations and schedules
        $resources->load('locations.schedules');

        $allAvailability = $this->getSchedulesForDate->get($resources, $service, $startDate, $endDate);

        // Initialize total count of appointments
        $totalAppointmentCount = 0;

        // Iterate through each resource
        foreach ($resources as $resource) {

            // get the availability for the resource
            $foundResource = $allAvailability->firstWhere(function($item) use ($resource) {
                return $item['resource']->id === $resource->id;
            });

            // Count the number of appointment slots in each period and add to the total
            foreach ($foundResource['periods'] as $period) {
                // Convert start and end times to minutes since midnight
                $startTimeMinutes = ($period->start()->format('H') * 60) + $period->start()->format('i');
                $endTimeMinutes = ($period->end()->format('H') * 60) + $period->end()->format('i');

                // Adjust start and end times to the next and last standard appointment time, respectively
                $adjustedStartTimeMinutes = (int)ceil($startTimeMinutes / $service->duration) * $service->duration;
                $adjustedEndTimeMinutes = (int)floor($endTimeMinutes / $service->duration) * $service->duration;

                // Calculate the length of the adjusted period
                $adjustedLengthMinutes = max(0, $adjustedEndTimeMinutes - $adjustedStartTimeMinutes);

                // Count the number of appointments that can fit into the adjusted period
                $totalAppointmentCount += intdiv($adjustedLengthMinutes, $service->duration); // appointment duration is fixed at 15 minutes
            }
        }
        return $totalAppointmentCount;
    }
}
