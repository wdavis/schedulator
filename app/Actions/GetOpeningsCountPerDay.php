<?php

namespace App\Actions;

use App\Models\Service;
use App\Traits\GradesColors;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

class GetOpeningsCountPerDay
{
    use GradesColors;

    private GetSchedulesForDate $getSchedulesForDate;
    private SplitPeriodIntoIntervals $splitPeriodIntoIntervals;

    public function __construct(GetSchedulesForDate $getSchedulesForDate, SplitPeriodIntoIntervals $splitPeriodIntoIntervals)
    {
        $this->getSchedulesForDate = $getSchedulesForDate;
        $this->splitPeriodIntoIntervals = $splitPeriodIntoIntervals;
    }

    public function get(Collection $resources, Service $service, CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        $schedules = $this->getSchedulesForDate->get($resources, $service, $startDate, $endDate, scopeLeadTimes: false);

        // now we have every resources schedule for the requested range
        // we need to return all the count slots by day with the count ofcount

        $results = [];

        // Fill in placeholder data for the entire date range
        for ($date = $startDate; $date <= $endDate; $date = $date->addDay()) {
            $dateString = $date->toDateString();
            $results[$dateString] = [
                'count' => 0,
                'color' => null,
                'slotsByHour' => [] // You can fill with default hourly slots if needed
            ];
        }

        foreach($schedules as $schedule) {

            // take the periods and split them into hours
            // then we can loop through each of the hours and add them to the results array
            $openPeriods = $this->splitPeriodIntoIntervals->execute($schedule['periods'], $service);

            foreach ($openPeriods as $item) {
                $start = $item['start'];
//                $end = $item['end'];
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
            }

        }

        return $this->gradeColors($results, startColor: '#ff0000', endColor: '#00ff00');

//        return $results;

    }
}
