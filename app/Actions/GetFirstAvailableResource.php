<?php

namespace App\Actions;

use App\Exceptions\BookingTimeSlotNotAvailableException;
use App\Exceptions\NoResourceAvailabilityForRequestedTimeException;
use App\Models\Service;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

class GetFirstAvailableResource
{
    private GetSchedulesForDate $getSchedulesForDate;
    private CheckScheduleAvailability $checkScheduleAvailability;

    public function __construct(GetSchedulesForDate $getSchedulesForDate, CheckScheduleAvailability $checkScheduleAvailability)
    {
        $this->getSchedulesForDate = $getSchedulesForDate;
        $this->checkScheduleAvailability = $checkScheduleAvailability;
    }

    // is the idea to use GetCombinedSchedulesForDate to find availability,
    // then use this to find the first available resource?
    public function get(Collection $resources, Service $service, CarbonImmutable $requestedDate)
    {
        // we'll take the sort order of the resources as the order of preference
        // so, they could be provided sorted by priority, or by distance, or by some other metric
        $schedules = $this->getSchedulesForDate->get($resources, $service, $requestedDate, $requestedDate);

        // now we have to look at each of the schedules and find the first that has an opening
        foreach ($schedules as $schedule) {
            if ($this->checkScheduleAvailability->check(
                openAvailability: $schedule['periods'],
                requestedStartTime: $requestedDate,
                duration: $service->duration
            )) {
                return $schedule['resource']->id;
            }
        }

        // if we didn't find anything above, we do not have an opening
        throw new NoResourceAvailabilityForRequestedTimeException("No time slots available for {$requestedDate->toIso8601String()}");
    }
}
