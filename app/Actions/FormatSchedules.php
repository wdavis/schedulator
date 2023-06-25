<?php

namespace App\Actions;

namespace App\Actions;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class FormatSchedules
{
    public function format(Collection $schedules)
    {
        $groupedSchedules = $schedules->groupBy('day_of_week');

        // Mapping day numbers back to their string representations
        $dayOfWeekMap = [
            1 => 'monday',
            2 => 'tuesday',
            3 => 'wednesday',
            4 => 'thursday',
            5 => 'friday',
            6 => 'saturday',
            7 => 'sunday',
        ];

        // The sortedDays array will hold the sorted keys (day of the week numbers)
        $sortedDays = $groupedSchedules->keys()->sort()->values();

        $transformedSchedules = $sortedDays->mapWithKeys(function ($day) use ($groupedSchedules, $dayOfWeekMap) {
            $sortedTimes = $groupedSchedules[$day]->sortBy('start_time')->values();

            return [
                $dayOfWeekMap[$day] => $sortedTimes->map(function ($schedule) {
                    return [
                        'start_time' => Carbon::parse($schedule->start_time)->format('H:i:s'),
                        'end_time' => Carbon::parse($schedule->end_time)->format('H:i:s'),
                    ];
                }),
            ];
        });


        return $transformedSchedules;
    }
}
