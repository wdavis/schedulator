<?php

namespace App\Actions;

use App\Models\Location;
use App\Models\Resource;
use App\Models\Schedule;
use Carbon\CarbonImmutable;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UpdateSchedule
{
    private FormatSchedules $formatSchedules;

    public function __construct(FormatSchedules $formatSchedules)
    {
        $this->formatSchedules = $formatSchedules;
    }

    public function execute(Resource $resource, array $scheduleData, ?Location $location = null): Collection
    {
        // Define a map of weekday names to their corresponding numbers.
        $dayOfWeekMap = [
            'monday' => 1,
            'tuesday' => 2,
            'wednesday' => 3,
            'thursday' => 4,
            'friday' => 5,
            'saturday' => 6,
            'sunday' => 7,
        ];

        // If no schedule data is provided, throw an exception.
        if (empty($scheduleData)) {
            throw new Exception('Schedule data is required');
        }

        // If location is not provided, find the primary location for this resource.
        if ($location === null) {
            $location = $resource->location; // You might need to adjust this according to your setup.

            if ($location === null) {
                throw new Exception('No location provided and the resource does not have a primary location');
            }
        }

        // Preload schedules for the provided resource and location
        $existingSchedules = Schedule::where('resource_id', $resource->id)
            ->where('location_id', $location->id)
            ->get();

        DB::transaction(function () use ($dayOfWeekMap, $scheduleData, $resource, $location, $existingSchedules) {
            foreach ($scheduleData as $day => $schedules) {

                // Collect all the ids of the existing schedules
                $existingScheduleIds = $existingSchedules->pluck('id');

                // Delete all existing schedules using the collected ids.
                Schedule::whereIn('id', $existingScheduleIds)->delete();

                // Check if the provided day name is valid.
                if (! isset($dayOfWeekMap[$day])) {
                    throw new Exception("Invalid day provided: $day");
                }

                // Delete existing schedules for the specific day of the week
                $existingSchedules->where('day_of_week', $dayOfWeekMap[$day])->each(function ($schedule) {
                    $schedule->delete();
                });

                // Create new schedules
                foreach ($schedules as $schedule) {
                    // Check if all required values are present.
                    if (! isset($schedule['start_time'], $schedule['end_time'])) {
                        throw new Exception('Start time and end time are required for each schedule');
                    }

                    $newSchedule = new Schedule;
                    $newSchedule->resource_id = $resource->id;
                    $newSchedule->location_id = $location->id;
                    $newSchedule->day_of_week = $dayOfWeekMap[$day];
                    $newSchedule->start_time = CarbonImmutable::parse($schedule['start_time'])->format('H:i:s');
                    $newSchedule->end_time = CarbonImmutable::parse($schedule['end_time'])->format('H:i:s');
                    $newSchedule->save();

                    //                    Schedule::create([
                    //                        'resource_id' => $resource->id,
                    //                        'location_id' => $location->id,
                    //                        'day_of_week' => $dayOfWeekMap[$day],
                    //                        'start_time' => CarbonImmutable::parse($schedule['start_time'])->format('H:i:s'),
                    //                        'end_time' => CarbonImmutable::parse($schedule['end_time'])->format('H:i:s'),
                    //                    ]);
                }
            }
        });

        $schedules = Schedule::where('resource_id', $resource->id)->where(function ($query) use ($location) {
            if ($location !== null) {
                $query->where('location_id', $location->id);
            }
        })->get();

        return $this->formatSchedules->format($schedules);
    }
}
