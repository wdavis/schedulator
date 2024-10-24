<?php

namespace App\Actions;

use Carbon\CarbonImmutable;

class GroupOpeningsByDay
{
    public function execute(array $openings, CarbonImmutable $startDate, CarbonImmutable $endDate, ?string $timezone = null): array
    {
        $groupedOpenings = [];

        foreach ($openings as $opening) {
            $start = CarbonImmutable::parse($opening['start'])->setTimezone($timezone);;
            $end = CarbonImmutable::parse($opening['end'])->setTimezone($timezone);;
            $date = $start->toDateString();
            $day = $start->format('D');

            if (!isset($groupedOpenings[$date])) {
                $groupedOpenings[$date] = [
                    'date' => $date,
                    'day' => $day,
                    'slots' => [],
                ];
            }

            $groupedOpenings[$date]['slots'][] = [
                'start' => $start->toISO8601String(),
                'end' => $end->toISO8601String(),
            ];
        }

        $values = $groupedOpenings;

        $finalValues = [];
        for ($date = $startDate; $date->lte($endDate); $date = $date->addDay()) {
            $dateString = $date->setTimezone($timezone)->toDateString();
            if (!isset($values[$dateString])) {
                $values[$dateString] = [
                    'date' => $dateString,
                    'day' => $date->setTimezone($timezone)->format('D'),
                    'slots' => [],
                ];
            }
            $finalValues[] = $values[$dateString];
        }

        // Sort array by date
        usort($finalValues, function ($a, $b) {
            return strcmp($a['date'], $b['date']);
        });

        return $finalValues;
    }
}
