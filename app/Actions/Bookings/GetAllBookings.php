<?php

namespace App\Actions\Bookings;

use App\Models\Booking;
use App\Models\Resource;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

class GetAllBookings
{
    /**
     * @param Collection<Resource> $resources
     * @param CarbonImmutable $startDate
     * @param CarbonImmutable $endDate
     * @param string|null $locationId
     * @return Collection<Booking>
     */
    public function get(Collection $resources, CarbonImmutable $startDate, CarbonImmutable $endDate, ?string $locationId = null, ?string $serviceId = null, bool $includeCancelled = false): Collection
    {
        // resource, location, service
        return Booking::whereIn('resource_id', $resources->pluck('id'))
            ->when(!$includeCancelled, function($query) {
                $query->where('cancelled_at', null);
            })
            ->when($serviceId, function($query) use ($serviceId) {
                $query->where('service_id', $serviceId);
            })
            ->when($locationId, function($query) use ($locationId) {
                $query->where('location_id', $locationId);
            })
            ->where(function($query) use ($startDate, $endDate) {
                $query->where('starts_at', '>=', $startDate)
                    ->where('ends_at', '<=', $endDate);
            })->get();
    }
}
