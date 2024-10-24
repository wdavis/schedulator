<?php

namespace App\Actions;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class FormatSchedules
{
    public function format(Collection $schedules)
    {
        // Mapping day numbers to their string representations
        $dayOfWeekMap = [
            1 => 'monday',
            2 => 'tuesday',
            3 => 'wednesday',
            4 => 'thursday',
            5 => 'friday',
            6 => 'saturday',
            7 => 'sunday',
        ];

        // Grouping schedules by 'day_of_week'
        $groupedSchedules = $schedules->groupBy('day_of_week');

        // Initialize an empty array for transformed schedules
        $transformedSchedules = collect();

        // Loop through all days of the week to ensure no days are missed
        foreach ($dayOfWeekMap as $dayNumber => $dayName) {
            if ($groupedSchedules->has($dayNumber)) {
                // Sort the times and format them if the day exists in the grouped schedules
                $sortedTimes = $groupedSchedules[$dayNumber]->sortBy('start_time')->values();

                // Map the sorted times to the formatted array
                $transformedSchedules[$dayName] = $sortedTimes->map(function ($schedule) {
                    return [
                        'start_time' => Carbon::parse($schedule->start_time)->format('H:i:s'),
                        'end_time' => Carbon::parse($schedule->end_time)->format('H:i:s'),
                    ];
                });
            } else {
                // If the day is missing, add an empty array
                $transformedSchedules[$dayName] = [];
            }
        }

        return $transformedSchedules;
    }
}
