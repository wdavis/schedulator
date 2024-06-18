<?php

namespace App\Http\Controllers\Api;

use App\Actions\Bookings\GetAllBookings;
use App\Models\Resource;
use App\Traits\GradesColors;
use App\Traits\InteractsWithEnvironment;
use Carbon\CarbonImmutable;

class ForecastBookingsController
{
    use InteractsWithEnvironment;
    use GradesColors;

    protected GetAllBookings $getAllBookings;

    /**
     * @param GetAllBookings $getAllBookings
     */
    public function __construct(GetAllBookings $getAllBookings)
    {
        $this->getAllBookings = $getAllBookings;
    }

    public function index()
    {
        $startRange = request('startDate', null);
        $endRange = request('endDate', null);

        $startDate = CarbonImmutable::parse($startRange);
        $endDate = CarbonImmutable::parse($endRange);

        $serviceId = request('serviceId', null);

        $resourceIds = request('resourceIds', null);

        $resources = Resource::query()
            ->when($resourceIds, fn($query) => $query->whereIn('id', $resourceIds))
            ->where('environment_id', $this->getApiEnvironmentId())
            ->get();

        // get resources
        $bookings = $this->getAllBookings->get(
            $resources,
            $startDate,
            $endDate,
            serviceId: $serviceId
        );

        $results = [];

        for ($date = $startDate; $date <= $endDate; $date = $date->addDay()) {
            $dateString = $date->toDateString();
            $results[$dateString] = [
                'count' => 0,
                'color' => null,
                'slotsByHour' => [] // You can fill with default hourly slots if needed
            ];
        }

        foreach ($bookings as $item) {
            $start = $item->starts_at;
            $end = $item->ends_at;
            $currentDate = $start->toDateString();
            $hourKey = $start->format('H:00:00') . '-' . $start->addHour()->format('H:00:00');

            if (!isset($results[$currentDate])) {
                $results[$currentDate] = [
                    'count' => 0,
                    'color' => null,
                    'slotsByHour' => []
                ];
            }

            $results[$currentDate]['count'] += 1;

            if (!isset($results[$currentDate]['slotsByHour'][$hourKey])) {
                $results[$currentDate]['slotsByHour'][$hourKey] = [
                    'color' => null,
                    'count' => 0,
                    'resources' => []
                ];
            }

            $results[$currentDate]['slotsByHour'][$hourKey]['count'] += 1;
            // Add the extra information from this dataset, such as ID and meta, if needed
            $results[$currentDate]['slotsByHour'][$hourKey]['resources'][] = [
                'id' => $item['id'],
                'meta' => $item['meta']
            ];
        }

        return $this->gradeColors($results, startColor: '#ffffff', endColor: '#ff0000');
//        return $bookings;
        // appointments booked vs appointments open for range

        // get combined schedules for date count
    }
}
