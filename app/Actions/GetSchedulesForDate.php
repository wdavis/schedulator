<?php

namespace App\Actions;

use App\Actions\Bookings\GetAllBookings;
use App\Models\Resource;
use App\Models\Service;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Period\Period;
use Spatie\Period\PeriodCollection;

class GetSchedulesForDate
{
    private GetScheduleOverrides $getScheduleOverrides;
    private BuildScheduleOverrides $buildScheduleOverrides;
    private BuildRecurringSchedule $buildRecurringSchedule;
    private GetAllBookings $getAllBookings;
    private BuildBookingPeriods $buildBookingPeriods;
    private ScopeAvailabilityWithLeadTime $scopeAvailabilityWithLeadTime;

    public function __construct(
        GetScheduleOverrides   $getScheduleOverrides,
        BuildScheduleOverrides $buildScheduleOverrides,
        BuildRecurringSchedule $buildRecurringSchedule,
        GetAllBookings $getAllBookings,
        BuildBookingPeriods $buildBookingPeriods,
        ScopeAvailabilityWithLeadTime $scopeAvailabilityWithLeadTime
    ){
        $this->getScheduleOverrides = $getScheduleOverrides;
        $this->buildScheduleOverrides = $buildScheduleOverrides;
        $this->buildRecurringSchedule = $buildRecurringSchedule;
        $this->getAllBookings = $getAllBookings;
        $this->buildBookingPeriods = $buildBookingPeriods;
        $this->scopeAvailabilityWithLeadTime = $scopeAvailabilityWithLeadTime;
    }

    /**
     * @param Collection<Resource> $resources
     * @param CarbonImmutable $startDate
     * @param CarbonImmutable $endDate
     * @return Collection<PeriodCollection>
     */
    public function get(Collection $resources, Service $service, CarbonImmutable $startDate, CarbonImmutable $endDate, bool $scopeLeadTimes = true): \Illuminate\Support\Collection
    {
        // check if any of the resources are inactive and ignore them
        $resources = $resources->filter(function($resource) {
            return $resource->active;
        });

        // for now just load all the resources locations and schedules
        $resources->load('locations.schedules');

        // get all the resource id's
        $resourceIds = $resources->pluck('id')->toArray();

        // preload the schedule overrides and bookings
        $availableOverrides = $this->getScheduleOverrides->get($resourceIds, $startDate, $endDate);
        $allBookings = $this->getAllBookings->get($resources, startDate: $startDate, endDate: $endDate);

        $schedules = collect();

        /** @var Resource $resource */
        foreach($resources as $resource) {
            $resourceSchedule = $resource->locations->pluck('schedules')->flatten();

            // build overrides for the date
            $overrides = $this->buildScheduleOverrides->get($availableOverrides->where('resource_id', $resource->id));

            // Build the recurring schedule for the given date
            $recurring = $this->buildRecurringSchedule->build($resourceSchedule, $startDate, $endDate, timezone: $resource->getMeta('timezone'));

//            if($bookings->isEmpty()) {
//                return $schedule; // todo why are we doing this?
//            }

            $bookingPeriods = $this->buildBookingPeriods->build($allBookings->where('resource_id', $resource->id));

            $periods = $recurring
                ->add(...$overrides['opening']) // merge in the openings
                ->subtract($overrides['block']) // subtract out the schedule blocks
                ->subtract($bookingPeriods)
                ->union()
                ->filter(function(Period $period) { // filter out any periods that are 0 seconds long
                    return $period->start() != $period->end();
                });

            if($scopeLeadTimes) {
                $periods = $this->scopeAvailabilityWithLeadTime->scope(
                    availability: $periods,
                    leadTimeInMinutes: $resource->bookingWindowEndOverride() ?? $service->booking_window_end,
                    bookingDurationInMinutes: $service->duration,
//                requestedStartDate: $startDate
                );
            }


            $schedule = [
                'resource' => $resource,
                'location_id' => $resource->locations->pluck('id')->toArray(),
                'location' => $resource->locations->pluck('name')->toArray(),
                'periods' => $periods,
                'bookings' => $allBookings->where('resource_id', $resource->id),
                // todo potentially include bookings, overrides here
            ];

            $schedules->push($schedule);
        }

        return $schedules;
    }
}
