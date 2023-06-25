<?php

namespace App\Http\Controllers\Api;

use App\Actions\FormatValidationErrors;
use App\Actions\GetAvailabilityForDate;
use App\Actions\GetCombinedScheduleForDate;
use App\Models\Resource;
use App\Models\Service;
use Carbon\CarbonImmutable;
use DateInterval;
use DateTimeImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\Period\Period;
use Spatie\Period\Precision;
use Spatie\Period\Boundaries;

class SlotController
{
    private GetCombinedScheduleForDate $getCombinedScheduleForDate;
    private FormatValidationErrors $formatValidationErrors;
    private GetAvailabilityForDate $getAvailabilityForDate;

    public function __construct(GetCombinedScheduleForDate $getCombinedScheduleForDate, FormatValidationErrors $formatValidationErrors, \App\Actions\GetAvailabilityForDate $getAvailabilityForDate)
    {
        $this->getCombinedScheduleForDate = $getCombinedScheduleForDate;
        $this->formatValidationErrors = $formatValidationErrors;
        $this->getAvailabilityForDate = $getAvailabilityForDate;
    }

    private function splitPeriodIntoIntervals(Period $period, DateInterval $interval): array
    {
        $start = $period->start();
        $end = $period->end();

        $periods = [];

        while ($start < $end) {
            $newEnd = (clone $start)->add($interval);

            if ($newEnd > $end) {
                $newEnd = $end;
            }

            $periods[] = Period::make($start, $newEnd, Precision::MINUTE());

            $start = $newEnd;
        }

        return $periods;
    }

    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'startDate' => 'required|date',
            'endDate' => 'required|date',
            'serviceId' => 'required|exists:services,id',
        ]);

        if ($validator->fails()) {
            return response()->json($this->formatValidationErrors->validate($validator->errors()->getMessages()), 422);
        }

        $start = $request->get('startDate');
        $end = $request->get('endDate');
        $resourceId = $request->route()->parameter('resource');
        $serviceId = $request->get('serviceId');

        $service = Service::find($serviceId);

        // todo look at the service id and get the lead times

        // todo also need to scope the slots to the current time of day

        $startDate = CarbonImmutable::parse($start);

        if(!$end) {
            $endDate = $startDate->endOfDay();
        } else {
            $endDate = CarbonImmutable::parse($end)->endOfDay();
        }

        $resource = Resource::find($resourceId);

        $schedule = $this->getAvailabilityForDate->get($resource, $startDate);

        $slots = [];

        foreach($schedule as $period) {

            // todo get interval from the service
            $interval = new DateInterval('PT'.$service->duration.'M');

            $periods = $this->splitPeriodIntoIntervals($period, $interval);

            foreach ($periods as $p) {
                $slots[] = [
                    'start' => CarbonImmutable::parse($p->start()),
                    'end' => CarbonImmutable::parse($p->end())
                ];
            }
        }

        return response()->json($slots);
    }
}
