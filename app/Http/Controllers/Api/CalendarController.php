<?php

namespace App\Http\Controllers\Api;

use App\Actions\CombinePeriodCollections;
use App\Actions\FormatValidationErrors;
use App\Actions\GetCombinedSchedulesForDate;
use App\Actions\GetSchedulesForDate;
use App\Actions\ScopeAvailabilityWithLeadTime;
use App\Actions\ScopeSchedule;
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

class CalendarController
{
    use InteractsWithEnvironment;

    private FormatValidationErrors $formatValidationErrors;
//    private GetAllAvailabilityForDate $getAvailabilityForDate;
//    private GetCombinedSchedulesForDate $getCombinedSchedulesForDate;

    private ScopeAvailabilityWithLeadTime $scopeAvailabilityWithLeadTime;
    private GetSchedulesForDate $getSchedulesForDate;
    private CombinePeriodCollections $combinePeriodCollections;
    private ScopeSchedule $scopeSchedule;

    public function __construct(
        FormatValidationErrors $formatValidationErrors,
        \App\Actions\ScopeAvailabilityWithLeadTime $scopeAvailabilityWithLeadTime,
//        GetCombinedSchedulesForDate $getCombinedSchedulesForDate,
        GetSchedulesForDate $getSchedulesForDate,
        CombinePeriodCollections $combinePeriodCollections,
        ScopeSchedule $scopeSchedule
    ) {
        $this->formatValidationErrors = $formatValidationErrors;
//        $this->getAvailabilityForDate = $getAvailabilityForDate;
        $this->scopeAvailabilityWithLeadTime = $scopeAvailabilityWithLeadTime;
//        $this->getCombinedSchedulesForDate = $getCombinedSchedulesForDate;
        $this->getSchedulesForDate = $getSchedulesForDate;
        $this->combinePeriodCollections = $combinePeriodCollections;
        $this->scopeSchedule = $scopeSchedule;
    }

    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'startDate' => ['required', new Iso8601Date()],
            'endDate' => ['required', new Iso8601Date()],
            'serviceId' => 'required|exists:services,id',
            'resourceIds' => 'array|nullable',
            'format' => ['string', 'in:list,days', 'nullable'],
            'timezone' => 'string|nullable'
        ]);

        if ($validator->fails()) {
            return response()->json($this->formatValidationErrors->validate($validator->errors()->getMessages()), 422);
        }

        $start = $request->get('startDate');
        $end = $request->get('endDate');
        $serviceId = $request->get('serviceId');
        $timezone = $request->get('timezone');

        $service = Service::where('id', $serviceId)
            ->where('environment_id', $this->getApiEnvironmentId())
            ->firstOrFail()
        ;

        $requestedDate = CarbonImmutable::parse($start)->setTimezone($timezone)->startOfDay();
        $requestedEndDate = CarbonImmutable::parse($end)->setTimezone($timezone);

        if(!$end) {
            $endDate = $requestedDate->endOfDay();
        } else {
            $endDate = $requestedEndDate->endOfDay();
        }

//        if($requestedDate->isPast() && $requestedEndDate->isPast()) {
//            return response()->json([
//                'message' => 'The requested range is in the past'
//            ], 422);
//        }

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

//        $availability = $this->getCombinedSchedulesForDate->get($resources, $service, $requestedDate, endDate: $endDate);
        $schedules = $this->getSchedulesForDate->get(
            $resources,
            $service,
            startDate: $requestedDate,
            endDate: $endDate,
            scopeLeadTimes: false, // we're viewing everything. will not be able to actually schedule without lead times
        );

        $combinedAvailability = $this->combinePeriodCollections->combine($schedules, key: 'periods');

//        $currentTimeOfDay = CarbonImmutable::now();

        // todo add parameter to not scope
        $availability = $combinedAvailability;
        // remove anything that is in the past by using the current time of day
//        $availability = $this->scopeAvailabilityWithLeadTime->scope(
//            $combinedAvailability,
//            leadTimeInMinutes: 0,
//            bookingDurationInMinutes: $service->duration,
//            requestedStartDate: $currentTimeOfDay
//        );

        $availability = $this->scopeSchedule->scope($availability, $requestedDate, $requestedEndDate);

        // todo inject?
        $splitPeriodIntoIntervals = new \App\Actions\SplitPeriodIntoIntervals();
        $slots = $splitPeriodIntoIntervals->execute($availability, $service);

        // todo all the bookings are in $schedules for each resource
        // flat map them into a single collection
        $getPeriod = new \App\Actions\Bookings\GetBookingPeriod();
        $bookings = collect($schedules)->flatMap(function($schedule) use ($getPeriod, $service, $requestedDate, $requestedEndDate) {

            // all the bookings need to be scoped to the requested date
//            $bookings = $this->scopeSchedule->scope($schedule['bookings'], $requestedDate, $requestedEndDate);

            return collect($schedule['bookings'])

                ->map(function($booking) use ($getPeriod, $service) {
                return $getPeriod->get($booking, $service);
            })->filter(function($booking) use ($requestedDate, $requestedEndDate) {
                    // check if the booking is within the bounds of the $requestedDate and $requestedEndDate
                    return $booking['period']->start()->isBetween($requestedDate, $requestedEndDate, Precision::MINUTE(), Boundaries::EXCLUDE_END());
                });
        });

        // todo inject?
        $getScheduleByDay = new \App\Actions\GetScheduleByDay();
        $slots = $getScheduleByDay->execute($slots, $bookings, $requestedDate, $requestedEndDate, $timezone);

        return response()->json($slots);
    }
}
