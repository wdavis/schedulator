<?php

namespace App\Actions\Bookings;

use App\Models\Booking;

class RescheduleBooking
{
    private CreateBooking $createBooking;

    private CancelBooking $cancelBooking;

    public function __construct(CreateBooking $createBooking, CancelBooking $cancelBooking)
    {
        $this->createBooking = $createBooking;
        $this->cancelBooking = $cancelBooking;
    }

    public function reschedule(string $bookingId, string $newTimeSlot, string $environmentId, ?string $newResourceId = null, ?string $newServiceId = null, array $meta = [], bool $force = false): Booking
    {
        $oldBooking = Booking::findOrFail($bookingId);

        try {

            // create the new oldBooking first
            $newBooking = $this->createBooking->create(
                resourceId: $newResourceId ?? $oldBooking->resource_id,
                serviceId: $newServiceId ?? $oldBooking->service_id,
                timeSlot: $newTimeSlot,
                environmentId: $environmentId,
                name: $oldBooking->name,
                meta: array_merge($oldBooking->meta, $meta, ['previous_starts_at' => $oldBooking->starts_at->toIso8601String()]),
                bypassLeadTime: $force,
                bypassActive: $force
            );

            $this->cancelBooking->cancel($oldBooking, force: $force);

            return $newBooking;

        } catch (\Exception $e) {

            if (isset($newBooking)) {
                $newBooking->delete();
            }

            throw $e;
        }

    }
}
