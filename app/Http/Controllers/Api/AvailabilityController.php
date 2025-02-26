<?php

namespace App\Http\Controllers\Api;

use App\Actions\FormatValidationErrors;
use App\Actions\GetCombinedSchedulesForDate;
use App\Actions\ScopeAvailabilityWithLeadTime;
use App\Models\Resource;
use App\Models\Service;
use App\Rules\Iso8601Date;
use App\Traits\InteractsWithEnvironment;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\Period\Boundaries;
use Spatie\Period\Period;
use Spatie\Period\Precision;

class AvailabilityController
{
    use InteractsWithEnvironment;

    private FormatValidationErrors $formatValidationErrors;

    //    private GetAllAvailabilityForDate $getAvailabilityForDate;
    private GetCombinedSchedulesForDate $getCombinedSchedulesForDate;

    private ScopeAvailabilityWithLeadTime $scopeAvailabilityWithLeadTime;

    public function __construct(
        FormatValidationErrors $formatValidationErrors,
        \App\Actions\ScopeAvailabilityWithLeadTime $scopeAvailabilityWithLeadTime,
        GetCombinedSchedulesForDate $getCombinedSchedulesForDate
    ) {
        $this->formatValidationErrors = $formatValidationErrors;
        //        $this->getAvailabilityForDate = $getAvailabilityForDate;
        $this->scopeAvailabilityWithLeadTime = $scopeAvailabilityWithLeadTime;
        $this->getCombinedSchedulesForDate = $getCombinedSchedulesForDate;
    }

    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'startDate' => ['required', new Iso8601Date],
            'endDate' => ['required', new Iso8601Date],
            'serviceId' => 'required|exists:services,id',
            'resourceIds' => 'array|nullable',
            'format' => ['string', 'in:list,days', 'nullable'],
            'timezone' => 'string|nullable',
        ]);

        if ($validator->fails()) {
            return response()->json($this->formatValidationErrors->validate($validator->errors()->getMessages()), 422);
        }

        $start = $request->get('startDate');
        $end = $request->get('endDate');
        $serviceId = $request->get('serviceId');
        $timezone = $request->get('timezone', 'UTC');

        try {
            $service = Service::where('id', $serviceId)
                ->where('environment_id', $this->getApiEnvironmentId())
                ->firstOrFail();
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Service not found',
            ], 404);
        }

        $requestedDate = CarbonImmutable::parse($start)->startOfDay()->setTimezone('UTC');
        $requestedEndDate = CarbonImmutable::parse($end)->endOfDay()->setTimezone('UTC');

        if ($requestedDate->isPast() && $requestedEndDate->isPast()) {
            return response()->json([
                'message' => 'The requested range is in the past',
            ], 422);
        }

        // check if the request has an array of resource ids

        $resourceIds = $request->get('resourceIds');

        // todo split this into an action
        $resources = Resource::where('environment_id', $this->getApiEnvironmentId())
            ->when($resourceIds, function ($query) use ($resourceIds) {
                $query->whereIn('id', $resourceIds);
            })
            ->where('active', true)
            ->get();
        $availability = $this->getCombinedSchedulesForDate->get($resources, $service, $requestedDate, endDate: $requestedEndDate);

        $currentTimeOfDay = CarbonImmutable::now();

        // remove anything that is in the past by using the current time of day
        $availability = $this->scopeAvailabilityWithLeadTime->scope(
            $availability,
            leadTimeInMinutes: 0,
            bookingDurationInMinutes: $service->duration,
            requestedStartDate: $currentTimeOfDay
        );

        //        // look at the end date
        //        $endScope = new Period(
        //            $requestedEndDate,
        //            $requestedEndDate,
        //            Precision::MINUTE(),
        //            Boundaries::EXCLUDE_ALL()
        //        );
        //        // strip off the extras
        //        $availability = $availability->subtract($endScope);

        // todo inject?
        $action = new \App\Actions\SplitPeriodIntoIntervals;
        $slots = $action->execute($availability, $service);

        // date format
        if ($request->get('format') === 'days') {

            //            $getScheduleByDay = new \App\Actions\GetScheduleByDay();
            //            $slots = $getScheduleByDay->execute(
            //                $slots,
            //                bookings: collect(),
            //                startDate: $requestedDate,
            //                endDate: $requestedEndDate,
            //                timezone: $timezone
            //            );

            $action = new \App\Actions\GroupOpeningsByDay;
            $slots = $action->execute($slots, $requestedDate, $requestedEndDate, $timezone);

            return response()->json($slots);
        }

        if ($timezone !== 'UTC') {
            // format dates in the requested timezone
            foreach ($slots as &$slot) {
                $slot['start'] = $slot['start']->setTimezone($timezone)->toIso8601String();
                $slot['end'] = $slot['end']->setTimezone($timezone)->toIso8601String();
            }
        } else {
            foreach ($slots as &$slot) {
                $slot['start'] = $slot['start']->toIso8601String();
                $slot['end'] = $slot['end']->toIso8601String();
            }
        }

        return response()->json($slots);
    }
}
