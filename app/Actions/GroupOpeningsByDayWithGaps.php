<?php

namespace App\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class GroupOpeningsByDayWithGaps
{
    public function execute(array $openings, Collection $bookings, ?string $timezone = null): array
    {
        $groupedOpenings = [];
        $previousEnd = null;

        foreach ($openings as $index => $opening) {
            $start = CarbonImmutable::parse($opening['start'])->setTimezone($timezone);
            $end = CarbonImmutable::parse($opening['end'])->setTimezone($timezone);
            $openings[$index]['start'] = $start;
            $openings[$index]['end'] = $end;
            $date = $start->toDateString();
            $day = $start->format('D');

            if (!isset($groupedOpenings[$date])) {
                $groupedOpenings[$date] = [
                    'date' => $date,
                    'day' => $day,
                    'slots' => [],
                ];
                $previousEnd = null; // reset for a new date
            }

            if ($previousEnd !== null && $start->greaterThan($previousEnd)) { // we need to gap
                $groupedOpenings[$date]['slots'][] = [
                    'type' => 'gap',
                    'start' => $previousEnd,
                    'end' => $start,
                ];
            }

            $groupedOpenings[$date]['slots'][] = [
                'type' => 'opening',
                'start' => $start,
                'end' => $end,
            ];

            $previousEnd = $end;
        }

        $values = array_values($groupedOpenings);

        // get the earliest opening time across all days

        $openings = collect($values)
            ->flatMap(fn($day) => $day['slots'])
            ->filter(fn($slot) => $slot['type'] === 'opening');

        $earliestOpening = $openings->sortBy(function($slot) {
            return $slot['start']->format('Hi');
        })->first()['start'];

        $latestOpening = $openings->sortBy(function($slot) {
            return $slot['end']->format('Hi');
        })->last()['end'];

        // add gaps at the start and end of the day using the first slot and last slot of each day. splice the values in to the correct location of the slots
        foreach($values as $index => $day) {
            // get the first slot of each day using array syntax
            $firstSlot = $day['slots'][0];

            $values[$index]['earliest'] = $earliestOpening->toISO8601String();
            $values[$index]['latest'] = $latestOpening->toISO8601String();

            // if slot time is equal to earliest opening time, then we don't need to add a gap
            if(intval($earliestOpening->format('Hi')) < intval($firstSlot['start']->format('Hi'))) {
                // add a gap at the start of the day
                array_unshift($values[$index]['slots'], [
                    'type' => 'gap',
                    'start' => $earliestOpening->setDate($firstSlot['start']->year, $firstSlot['start']->month, $firstSlot['start']->day),
                    'end' => $firstSlot['start'],
                ]);
            }

            // get the last slot of each day using array syntax
            $lastSlot = end($day['slots']);

            if(intval($latestOpening->format('Hi')) > intval($lastSlot['end']->format('Hi'))) {
                // add a gap at the start of the day
                $values[$index]['slots'][] = [
                    'type' => 'gap',
                    'start' => $lastSlot['end'],
                    'end' => $latestOpening->setDate($lastSlot['end']->year, $lastSlot['end']->month, $lastSlot['end']->day),
                ];
            }

            // run ->toISO8601String() on all start and end times
            foreach($values[$index]['slots'] as $slotIndex => $slot) {
                $values[$index]['slots'][$slotIndex]['start'] = $slot['start']->toISO8601String();
                $values[$index]['slots'][$slotIndex]['end'] = $slot['end']->toISO8601String();
            }
        }

        return $values;
    }
}
