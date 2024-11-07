<?php

namespace App\Actions;

use Carbon\CarbonImmutable;

class GroupOpeningsByDay
{
    public function execute(array $openings, CarbonImmutable $startDate, CarbonImmutable $endDate, ?string $timezone = null): array
    {
        $groupedOpenings = [];

        foreach ($openings as $opening) {
            $start = CarbonImmutable::parse($opening['start'])->setTimezone($timezone);
            $end = CarbonImmutable::parse($opening['end'])->setTimezone($timezone);
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

        $finalValues = [];
        $seenDates = []; // Track seen dates to avoid duplicates

        for ($date = $startDate; $date->lte($endDate); $date = $date->addDay()) {
            $dateWithTimezone = $date->setTimezone($timezone);
            $dateString = $dateWithTimezone->toDateString();

            // Check if the date has already been added to avoid duplicates
            if (!isset($seenDates[$dateString])) {
                $seenDates[$dateString] = true; // Mark this date as seen

                if (!isset($groupedOpenings[$dateString])) {
                    $groupedOpenings[$dateString] = [
                        'date' => $dateString,
                        'day' => $dateWithTimezone->format('D'),
                        'slots' => [],
                    ];
                }

                // Ensure proper handling of DST transitions
                $slots = $groupedOpenings[$dateString]['slots'];
//                foreach ($slots as &$slot) {
//                    $start = CarbonImmutable::parse($slot['start'])->setTimezone($timezone);
//                    $end = CarbonImmutable::parse($slot['end'])->setTimezone($timezone);
//
//                    // Handle the repeated hour during DST end
//                    if ($start->eq($end)) {
//                        $end = $end->addHour();
//                    }
//                    $slot['start'] = $start->toISO8601String();
//                    $slot['end'] = $end->toISO8601String();
//                }

                $groupedOpenings[$dateString]['slots'] = $slots;
                $finalValues[] = $groupedOpenings[$dateString];
            }
        }

        // Sort array by date to ensure order consistency
        usort($finalValues, function ($a, $b) {
            return strcmp($a['date'], $b['date']);
        });

        return $finalValues;
    }
}
