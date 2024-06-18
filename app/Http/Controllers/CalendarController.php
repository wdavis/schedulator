<?php

namespace App\Http\Controllers;

use App\Actions\FormatValidationErrors;
use App\Actions\GetCombinedSchedulesForDate;
use App\Actions\ScopeAvailabilityWithLeadTime;
use App\Models\Resource;
use App\Models\Service;
use App\Rules\Iso8601Date;
use App\Traits\InteractsWithEnvironment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;
use Spatie\Period\Boundaries;
use Spatie\Period\Period;
use Spatie\Period\Precision;

class CalendarController
{
    use InteractsWithEnvironment;

    private FormatValidationErrors $formatValidationErrors;
//    private GetAllAvailabilityForDate $getAvailabilityForDate;
    private GetCombinedSchedulesForDate $getCombinedSchedulesForDate;

    private ScopeAvailabilityWithLeadTime $scopeAvailabilityWithLeadTime;

    public function __construct(FormatValidationErrors $formatValidationErrors, \App\Actions\ScopeAvailabilityWithLeadTime $scopeAvailabilityWithLeadTime, GetCombinedSchedulesForDate $getCombinedSchedulesForDate)
    {
        $this->formatValidationErrors = $formatValidationErrors;
//        $this->getAvailabilityForDate = $getAvailabilityForDate;
        $this->scopeAvailabilityWithLeadTime = $scopeAvailabilityWithLeadTime;
        $this->getCombinedSchedulesForDate = $getCombinedSchedulesForDate;
    }

    public function index()
    {
        dd('here');

        $validator = Validator::make($request->all(), [
            'startDate' => ['required', new Iso8601Date()],
            'endDate' => ['required', new Iso8601Date()],
            'serviceId' => 'required|exists:services,id',
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
            ->firstOrFail()
        ;

        $requestedDate = CarbonImmutable::parse($start);
        $requestedEndDate = CarbonImmutable::parse($end);

        if(!$end) {
            $endDate = $requestedDate->endOfDay();
        } else {
            $endDate = CarbonImmutable::parse($end)->endOfDay();
        }

        if($requestedDate->isPast() && $requestedEndDate->isPast()) {
            return response()->json([
                'message' => 'The requested range is in the past'
            ], 422);
        }

        // check if the request has an array of resource ids

        $resourceIds = $request->get('resourceIds');

        // todo split this into an action
        $resources = Resource::where('environment_id', $this->getApiEnvironmentId())
            ->when($resourceIds, function($query) use ($resourceIds) {
                $query->whereIn('id', $resourceIds);
            })
            ->where('active', true)
            ->get()
        ;

        $availability = $this->getCombinedSchedulesForDate->get($resources, $service, $requestedDate, endDate: $endDate);

        $currentTimeOfDay = CarbonImmutable::now();

        // remove anything that is in the past by using the current time of day
        $availability = $this->scopeAvailabilityWithLeadTime->scope(
            $availability,
            leadTimeInMinutes: 0,
            bookingDurationInMinutes: $service->duration,
            requestedStartDate: $currentTimeOfDay
        );

        // look at the end date
        $endScope = new Period($requestedEndDate, $endDate->endOfDay(), Precision::MINUTE(), Boundaries::EXCLUDE_ALL());
        // strip off the extras
        $availability = $availability->subtract($endScope);

        // todo inject?
        $action = new \App\Actions\SplitPeriodIntoIntervals();
        $slots = $action->execute($availability, $service);

        return response()->json($slots);

    }
}
