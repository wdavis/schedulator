<?php

namespace App\Actions;

use App\Models\ScheduleOverride;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class ProcessScheduleOverrides
{
    public function execute(array $overrides, string $resourceId, string $locationId, string $month, string $timezone)
    {
        // Parse the month to get the start and end of the month
        $startOfMonth = CarbonImmutable::createFromFormat('Y-m', $month, $timezone)->startOfMonth();
        $endOfMonth = CarbonImmutable::createFromFormat('Y-m', $month, $timezone)->endOfMonth();

        return DB::transaction(function () use ($overrides, $resourceId, $locationId, $startOfMonth, $endOfMonth) {
            // Filter records only within the month
            $existingIds = collect($overrides)
                ->filter(fn ($o) => ! is_null($o['id']))
                ->pluck('id')
                ->toArray();

            // Step 2: Delete records that are no longer in the list, scoped by the month
            ScheduleOverride::where('resource_id', $resourceId)
                ->where('location_id', $locationId)
                ->whereBetween('starts_at', [$startOfMonth, $endOfMonth])
                ->whereNotIn('id', $existingIds)
                ->delete();

            $updatedRecords = [];

            // Step 3: Loop through the overrides to insert or update, scoped by the month
            foreach ($overrides as $override) {
                // Insert new record
                if (is_null($override['id'])) {
                    $record = ScheduleOverride::create([
                        'resource_id' => $resourceId,
                        'location_id' => $locationId,
                        'starts_at' => $override['starts_at'],
                        'ends_at' => $override['ends_at'],
                        'type' => $override['type'],
                    ]);
                } else {
                    // Update existing record if it falls within the month
                    $record = ScheduleOverride::where('id', $override['id'])
                        ->where('resource_id', $resourceId)
                        ->where('location_id', $locationId)
                        ->firstOrFail();

                    $record->update([
                        'starts_at' => $override['starts_at'],
                        'ends_at' => $override['ends_at'],
                        'type' => $override['type'],
                    ]);
                }

                $updatedRecords[] = $record;
            }

            // Step 4: Return the updated set of records within the month
            return ScheduleOverride::where('resource_id', $resourceId)
                ->where('location_id', $locationId)
                ->whereBetween('starts_at', [$startOfMonth->toIso8601ZuluString(), $endOfMonth->toIso8601ZuluString()])
                ->orderBy('starts_at')
                ->get();
        });
    }
}
