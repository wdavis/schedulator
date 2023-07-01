<?php

namespace App\Http\Controllers\Api;

use App\Actions\FormatValidationErrors;
use App\Actions\GetAllAvailabilityForDate;
use App\Models\Resource;
use App\Models\Service;
use App\Rules\Iso8601Date;
use App\Traits\InteractsWithEnvironment;
use Carbon\CarbonImmutable;
use DateInterval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\Period\Period;
use Spatie\Period\Precision;

class AvailabilityController
{
    use InteractsWithEnvironment;

    private FormatValidationErrors $formatValidationErrors;
    private GetAllAvailabilityForDate $getAvailabilityForDate;

    public function __construct(FormatValidationErrors $formatValidationErrors, \App\Actions\GetAllAvailabilityForDate $getAvailabilityForDate)
    {
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

        // todo look at the service id and get the lead times
        $leadTime = $service->buffer_before; // in minutes

        // todo also need to scope the slots to the current time of day, right now we're returning everything for the day

        $startDate = CarbonImmutable::parse($start);
        // todo need to check that date is an iso date

        if(!$end) {
            $endDate = $startDate->endOfDay();
        } else {
            $endDate = CarbonImmutable::parse($end)->endOfDay();
        }

        // check if the request has an array of resource ids

        $resourceIds = $request->get('resourceIds');

        $resources = Resource::where('environment_id', $this->getApiEnvironmentId())
            ->where(function($query) use ($resourceIds) {
                if($resourceIds) {
                    $query->whereIn('id', $resourceIds);
                }
            })
            ->where('active', true)
            ->get()
        ;

        ray($resources->pluck('id')->toArray());

        $schedule = $this->getAvailabilityForDate->get($resources, $startDate, endDate: $endDate);

        if($startDate->isToday()) { // scope the schedule to the current time of day
            // take the current time, and add the lead time to it
            $startDate = $startDate->addMinutes($leadTime);

            // take the start date and update the time to match the next interval
            $startDate = $startDate->addMinutes($service->duration - ($startDate->minute % $service->duration));

            $schedule = $schedule->intersect(Period::make($startDate, $endDate, Precision::MINUTE()));
        }

        $slots = [];

        $interval = new DateInterval('PT'.$service->duration.'M');

        foreach($schedule as $period) {

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
