<?php

namespace App\Actions;

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
    public function get(Collection $resources, CarbonImmutable $startDate, CarbonImmutable $endDate, string $locationId = null): Collection
    {
        // resource, location, service
        return Booking::whereIn('resource_id', $resources->pluck('id'))->where(function($query) use ($locationId) {
            if($locationId) {
                $query->where('location_id', $locationId);
            }
        })->where(function($query) use ($startDate, $endDate) {
            $query->where('starts_at', '>=', $startDate)
                ->where('ends_at', '<=', $endDate);
        })->get();
    }
}
