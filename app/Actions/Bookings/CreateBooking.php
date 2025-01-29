<?php

namespace App\Actions\Bookings;

use App\Actions\CheckScheduleAvailability;
use App\Actions\GetCombinedSchedulesForDate;
use App\Actions\ScopeAvailabilityWithLeadTime;
use App\Exceptions\BookingTimeSlotNotAvailableException;
use App\Exceptions\ResourceNotActiveException;
use App\Models\Booking;
use App\Models\Resource;
use App\Models\Service;
use Carbon\CarbonImmutable;

class CreateBooking
{
    private GetCombinedSchedulesForDate $getCombinedSchedulesForDate;

    private CheckScheduleAvailability $checkScheduleAvailability;

    private ScopeAvailabilityWithLeadTime $scopeAvailabilityWithLeadTime;

    public function __construct(GetCombinedSchedulesForDate $getCombinedSchedulesForDate, CheckScheduleAvailability $checkScheduleAvailability, ScopeAvailabilityWithLeadTime $scopeAvailabilityWithLeadTime)
    {
        $this->getCombinedSchedulesForDate = $getCombinedSchedulesForDate;
        $this->checkScheduleAvailability = $checkScheduleAvailability;
        $this->scopeAvailabilityWithLeadTime = $scopeAvailabilityWithLeadTime;
    }

    public function create(string $resourceId, string $serviceId, string $timeSlot, string $environmentId, string $name = '', array $meta = [], bool $bypassLeadTime = false, bool $bypassActive = false): Booking
    {
        $originalRequestedDate = CarbonImmutable::parse($timeSlot);

        $scheduleStartRange = $originalRequestedDate->startOfDay()->setTimezone('UTC');
        $scheduleEndRange = $originalRequestedDate->endOfDay()->setTimezone('UTC');

        $requestedDate = $originalRequestedDate->setTimezone('UTC');

        if (! $requestedDate) {
            throw new \Exception('Invalid date');
        }

        // get resources (get availability requires collection)
        $resources = Resource::where('id', $resourceId)
            ->where('environment_id', $environmentId)
            ->get();

        if ($resources->first()->active === false && ! $bypassActive) {
            throw new ResourceNotActiveException('Resource is not active');
        }

        $resources->load('location'); // gets the first location

        $service = Service::where('id', $serviceId)->firstOrFail();

        $availability = $this->getCombinedSchedulesForDate->get(
            resources: $resources,
            service: $service,
            startDate: $scheduleStartRange,
            endDate: $scheduleEndRange
        );

        $availableBeforeLead = $this->checkScheduleAvailability->check(
            $availability,
            requestedStartTime: $requestedDate,
            duration: $service->duration
        );

        if ($availableBeforeLead && $bypassLeadTime) {
            return $this->createBooking(
                $name,
                $resourceId,
                $requestedDate,
                $service,
                $resources->first()->location->id,
                array_merge($meta, ['bypassLeadTime' => true])
            );
        }

        $availability = $this->scopeAvailabilityWithLeadTime->scope(
            $availability,
            leadTimeInMinutes: $resources->first()->bookingWindowEndOverride() ?? $service->booking_window_end,
            bookingDurationInMinutes: $service->duration
        );

        $available = $this->checkScheduleAvailability->check(
            $availability,
            requestedStartTime: $requestedDate,
            duration: $service->duration
        );

        if ($availableBeforeLead && ! $available) {
            throw new BookingTimeSlotNotAvailableException($this->formatBookingTimeSlotError($originalRequestedDate, $requestedDate)." (lead time of {$service->booking_window_end} minutes required)");
        }

        if (! $available) {
            throw new BookingTimeSlotNotAvailableException($this->formatBookingTimeSlotError($originalRequestedDate, $requestedDate));
        }

        return $this->createBooking(
            $name,
            $resourceId,
            $requestedDate,
            $service,
            $resources->first()->location->id,
            $meta
        );
    }

    /**
     * Create Booking record
     *
     * Only use this method if you have already checked availability or are importing bookings
     */
    public function createBooking(string $name, string $resourceId, CarbonImmutable $requestedDate, Service $service, string $locationId, array $meta = [], bool $cancelled = false): Booking
    {
        // todo need to check if environment requires hipaa
        // if it does then the name gets set to something generic, or something like an external id

        $cancelledAt = null;

        if ($cancelled) {
            $cancelledAt = now();
        }

        // create booking
        return Booking::create([
            'name' => $name, // if in hipaa mode, this needs to be emptied
            'resource_id' => $resourceId,
            'location_id' => $locationId,
            'service_id' => $service->id,
            'starts_at' => $requestedDate,
            'ends_at' => $requestedDate->addMinutes($service->duration),
            'meta' => $meta,
            'cancelled_at' => $cancelledAt,
        ]);
    }

    private function formatBookingTimeSlotError(CarbonImmutable $date1, CarbonImmutable $date2): string
    {
        // if the iso8601 dates are different return date1 and date2
        if ($date1->toIso8601String() !== $date2->toIso8601String()) {
            return "Time slot [{$date1->toIso8601String()} ({$date2->toIso8601String()})] not available";
        }

        // now just return date1
        return "Time slot [{$date1->toIso8601String()}] not available";
    }
}
