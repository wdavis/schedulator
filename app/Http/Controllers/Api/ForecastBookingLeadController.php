<?php

namespace App\Http\Controllers\Api;

use App\Models\Booking;
use App\Models\Resource;
use App\Models\Service;
use App\Traits\InteractsWithEnvironment;
use Carbon\CarbonImmutable;

class ForecastBookingLeadController
{
    use InteractsWithEnvironment;

    public function index()
    {
        $startRange = request('startDate', null);
        $endRange = request('endDate', null);

        $startDate = CarbonImmutable::parse($startRange);
        $endDate = CarbonImmutable::parse($endRange);

        $serviceId = request('serviceId', null);

        $service = Service::where('id', $serviceId)
            ->where('environment_id', $this->getApiEnvironmentId())
            ->firstOrFail();

        $resourceIds = request('resourceIds', null);

        $resources = Resource::query()
            ->when($resourceIds, fn($query) => $query->whereIn('id', $resourceIds))
            ->where('environment_id', $this->getApiEnvironmentId())
            ->where('active', true)
            ->get();

        // these are all the openings for the given range
        $bookings = Booking::where('service_id', $serviceId)
            ->where('starts_at', '>=', $startDate)
            ->where('starts_at', '<=', $endDate)
            ->when($resourceIds, fn($query) => $query->whereIn('resource_id', $resourceIds))
            ->get();

        // look at the bookings within the start and end date
        // compare the created_at time vs the starts_at time
        // get the difference in minutes
        // group by the difference in minutes

        $bookings->each(function($booking) {
            $leadTime = $booking->starts_at->diffInMinutes($booking->created_at);
            $booking->leadTime = $leadTime;
        });

        $grouped = $bookings->groupBy('leadTime');

        $results = $grouped->map(function($group) {
            return $group->count();
        });

        return response()->json([
            'results' => $results,
        ]);




    }
}
