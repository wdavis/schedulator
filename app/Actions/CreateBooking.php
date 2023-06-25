<?php

namespace App\Actions;

use App\Exceptions\BookingTimeSlotNotAvailableException;
use App\Exceptions\ResourceNotActiveException;
use App\Models\Booking;
use App\Models\Resource;
use App\Models\Service;
use Carbon\CarbonImmutable;

class CreateBooking
{
    private GetAvailabilityForDate $getAvailabilityForDate;
    private CheckScheduleAvailability $checkScheduleAvailability;

    public function __construct(GetAvailabilityForDate $getAvailabilityForDate, CheckScheduleAvailability $checkScheduleAvailability)
    {
        $this->getAvailabilityForDate = $getAvailabilityForDate;
        $this->checkScheduleAvailability = $checkScheduleAvailability;
    }

    public function create(string $resourceId, string $serviceId, string $timeSlot, string $environmentId, string $name = "", array $meta = []): Booking
    {
        $originalRequestedDate = CarbonImmutable::parse($timeSlot);
        $requestedDate = $originalRequestedDate->setTimezone('UTC');

        if(!$requestedDate) {
            throw new \Exception('Invalid date');
        }

        // get schedule
        $resource = Resource::where('id', $resourceId)
            ->where('environment_id', $environmentId)
            ->firstOrFail();

        if($resource->active === false) {
            throw new ResourceNotActiveException('Resource is not active');
        }

        $resource->load('location'); // gets the first location


        ray($resource);

        $service = Service::where('id', $serviceId)->firstOrFail();

        $availability = $this->getAvailabilityForDate->get($resource, $requestedDate);

        $available = $this->checkScheduleAvailability->check(
            $availability,
            requestedStartTime: $requestedDate,
            duration: $service->duration
        );

        if(!$available) {
            throw new BookingTimeSlotNotAvailableException($this->formatBookingTimeSlotError($originalRequestedDate, $requestedDate));
        }

        // todo need to check if environment requires hipaa
        // if it does then the name gets set to something generic, or something like an external id

        // create booking
        return Booking::create([
            'name' => $name, // if in hipaa mode, this needs to be emptied
            'resource_id' => $resourceId,
            'location_id' => $resource->location->id,
            'service_id' => $serviceId,
            'starts_at' => $requestedDate,
            'ends_at' => $requestedDate->addMinutes($service->duration),
            'meta' => $meta
        ]);
    }

    private function formatBookingTimeSlotError(CarbonImmutable $date1, CarbonImmutable $date2): string
    {
        // if the iso8601 dates are different return date1 and date2
        if($date1->toIso8601String() !== $date2->toIso8601String()) {
            return "Time slot [{$date1->toIso8601String()} ({$date2->toIso8601String()})] not available";
        }

        // now just return date1
        return "Time slot [{$date1->toIso8601String()}] not available";
    }
}
