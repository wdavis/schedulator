<?php

namespace App\Http\Controllers\Api;

use App\Actions\GetOpeningsCountPerDay;
use App\Models\Resource;
use App\Models\Service;
use App\Traits\InteractsWithEnvironment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class ForecastHeatmapController
{
    use InteractsWithEnvironment;

    private GetOpeningsCountPerDay $getOpeningsCountPerDay;

    /**
     * @param GetOpeningsCountPerDay $getOpeningsCountPerDay
     */
    public function __construct(GetOpeningsCountPerDay $getOpeningsCountPerDay)
    {
        $this->getOpeningsCountPerDay = $getOpeningsCountPerDay;
    }

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
        $results = $this->getOpeningsCountPerDay->get(
            $resources,
            $service,
            $startDate,
            $endDate,
        );

        return response()->json([
            'queries' => DB::getQueryLog(),
            'results' => $results,
        ]);

        // get schedules for all resources in range, then by day get the count of openings?
    }
}
