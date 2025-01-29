<?php

namespace App\Http\Controllers\Api;

use App\Actions\FormatValidationErrors;
use App\Actions\GetCombinedSchedulesForDateCount;
use App\Models\Resource;
use App\Models\Service;
use App\Rules\Iso8601Date;
use App\Traits\InteractsWithEnvironment;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ForecastCountController
{
    use InteractsWithEnvironment;

    private GetCombinedSchedulesForDateCount $getCombinedSchedulesForDateCount;

    private FormatValidationErrors $formatValidationErrors;

    public function __construct(GetCombinedSchedulesForDateCount $getCombinedSchedulesForDateCount, FormatValidationErrors $formatValidationErrors)
    {
        $this->getCombinedSchedulesForDateCount = $getCombinedSchedulesForDateCount;
        $this->formatValidationErrors = $formatValidationErrors;
    }

    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'startDate' => ['required', new Iso8601Date],
            'endDate' => ['required', new Iso8601Date],
            'serviceId' => 'required',
            'resourceIds' => 'array|nullable',
        ]);

        if ($validator->fails()) {
            return response()->json($this->formatValidationErrors->validate($validator->errors()->getMessages()), 422);
        }

        $start = $request->get('startDate');
        $end = $request->get('endDate');
        $serviceId = $request->get('serviceId');

        $service = Service::where('id', $serviceId)
            ->where('environment_id', $this->getApiEnvironmentId())
            ->firstOrFail();

        $requestedDate = CarbonImmutable::parse($start)->startOfDay()->setTimezone('UTC');
        $requestedEndDate = CarbonImmutable::parse($end)->endOfDay()->setTimezone('UTC');

        //        if($requestedDate->isPast() && $requestedEndDate->isPast()) {
        //            return response()->json([
        //                'message' => 'The requested range is in the past'
        //            ], 422);
        //        }

        // check if the request has an array of resource ids

        $resourceIds = $request->get('resourceIds');

        $resources = Resource::where('environment_id', $this->getApiEnvironmentId())
            ->where(function ($query) use ($resourceIds) {
                if ($resourceIds) {
                    $query->whereIn('id', $resourceIds);
                }
            })
            ->where('active', true)
            ->get();

        $availability = $this->getCombinedSchedulesForDateCount->get($resources, $service, $requestedDate, endDate: $requestedEndDate);

        return response()->json([
            'availability' => $availability,
        ]);

    }
}
