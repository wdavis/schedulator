<?php

namespace App\Actions;

use Carbon\CarbonImmutable;

class GroupOpeningsByDay
{
    public function execute(array $openings, ?string $timezone = null): array
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

        return array_values($groupedOpenings);
    }
}
