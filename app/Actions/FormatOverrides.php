<?php

namespace App\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use App\Models\ScheduleOverride;

class FormatOverrides
{
    /**
     * Format the collection of ScheduleOverrides with placeholders for missing days.
     *
     * @param Collection $scheduleOverrides
     * @param CarbonImmutable $startDate
     * @param CarbonImmutable $endDate
     * @param string $timezone
     * @return Collection
     */
    public function format(Collection $scheduleOverrides, CarbonImmutable $startDate, CarbonImmutable $endDate, string $timezone = 'UTC'): Collection
    {
        // Step 1: Group the data by the date in the requested timezone (Y-m-d format)
        $groupedByDate = $scheduleOverrides->groupBy(function($item) use ($timezone) {
            return CarbonImmutable::parse($item->starts_at)->setTimezone($timezone)->format('Y-m-d');
        });

        // Step 2: Iterate through the date range provided, converting the output to the requested timezone
        $finalSchedule = collect();
        for ($date = $startDate->startOfDay(); $date->lte($endDate->endOfDay()); $date = $date->addDay()) {
            $formattedDate = $date->setTimezone($timezone)->format('Y-m-d');
            $dayOfWeek = $date->setTimezone($timezone)->format('D');

            // Add schedule data for the day, formatted in the requested timezone
            $finalSchedule->push([
                'date' => $formattedDate,
                'day' => $dayOfWeek,
                'schedule' => $groupedByDate->get($formattedDate, collect())->map(function ($item) use ($timezone) {
                    return [
                        'id' => $item->id,
                        'type' => $item->type,
                        'starts_at' => CarbonImmutable::parse($item->starts_at)->setTimezone($timezone)->toIso8601String(),
                        'ends_at' => CarbonImmutable::parse($item->ends_at)->setTimezone($timezone)->toIso8601String(),
                    ];
                })->toArray(), // Empty schedule array if the day has no records
            ]);
        }

        // Step 3: Return the final formatted schedule
        return $finalSchedule;
    }
}
