<?php

namespace App\Http\Controllers\Api;

use App\Actions\FormatValidationErrors;
use App\Actions\GetAvailabilityForDate;
use App\Actions\GetBookingsForDate;
use App\Actions\GetCombinedScheduleForDate;
use App\Models\Environment;
use App\Models\Location;
use App\Models\Resource;
use App\Actions\GetOpeningsForLocationAndPeriod;
use App\Rules\Iso8601Date;
use App\Rules\NotFromPast;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\Period\Boundaries;
use Spatie\Period\Period;
use Spatie\Period\Precision;

class ScheduleOpeningsController
{
    private GetCombinedScheduleForDate $getCombinedScheduleForDate;
    private GetAvailabilityForDate $getAvailabilityForDate;
    private FormatValidationErrors $formatValidationErrors;

    /**
     * @param GetCombinedScheduleForDate $getCombinedScheduleForDate
     * @param GetAvailabilityForDate $getAvailabilityForDate
     * @param FormatValidationErrors $formatValidationErrors
     */
    public function __construct(GetCombinedScheduleForDate $getCombinedScheduleForDate, \App\Actions\GetAvailabilityForDate $getAvailabilityForDate, \App\Actions\FormatValidationErrors $formatValidationErrors, )
    {
        $this->getCombinedScheduleForDate = $getCombinedScheduleForDate;
        $this->getAvailabilityForDate = $getAvailabilityForDate;
        $this->formatValidationErrors = $formatValidationErrors;
    }

    public function getOpenings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'startDate' => 'required|date',
            'endDate' => 'required|date',
        ]);

        if($validator->fails()) {
            return response()->json($this->formatValidationErrors->validate($validator->errors()->getMessages()), 422);
        }

        $start = $request->get('startDate');
        $end = $request->get('endDate');
        $resourceId = $request->route()->parameter('resource');
        $serviceId = $request->get('serviceId');

        $startDate = CarbonImmutable::parse($start);

        if(!$end) {
            $endDate = $startDate->endOfDay();
        } else {
            $endDate = CarbonImmutable::parse($end)->endOfDay();
        }

        $resource = Resource::find($resourceId);

//        $openings = $this->getCombinedScheduleForDate->get($resource, $startDate, $endDate);
        $openings = $this->getAvailabilityForDate->get($resource, $startDate);

        $slots = [];

        foreach($openings as $opening) {
            $slots[] = [
                'start' => CarbonImmutable::parse($opening->start()),
                'end' => CarbonImmutable::parse($opening->end())
            ];
        }

        return response()->json($slots);
    }
}
