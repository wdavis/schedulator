<?php

namespace App\Actions;

use App\Models\Booking;
use App\Models\Resource;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

class GetBookings
{
    /**
     * @param Resource $resource
     * @param CarbonImmutable $startDate
     * @param CarbonImmutable $endDate
     * @param string|null $locationId
     * @return Collection<Booking>
     */
    public function get(Resource $resource, CarbonImmutable $startDate, CarbonImmutable $endDate, string $locationId = null): Collection
    {
        // resource, location, service
        return Booking::where('resource_id', $resource->id)->where(function($query) use ($locationId) {
            if($locationId) {
                $query->where('location_id', $locationId);
            }
        })->where(function($query) use ($startDate, $endDate) {
            $query->where('starts_at', '>=', $startDate)
                ->where('ends_at', '<=', $endDate);
        })->get();
    }
}
