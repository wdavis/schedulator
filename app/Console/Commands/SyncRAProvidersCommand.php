<?php

namespace App\Console\Commands;

use App\Actions\Resources\CreateResource;
use App\Actions\UpdateSchedule;
use App\Models\Environment;
use Illuminate\Console\Command;

class SyncRAProvidersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ra:sync-providers {environmentId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(CreateResource $createResource, UpdateSchedule $updateSchedule)
    {
        $environmentId = $this->argument('environmentId');

        // find the environment
        $environment = Environment::where('id', $environmentId)
            ->firstOrFail();

        $this->info("Syncing providers for environment: {$environment->name}");

        $response = $this->getPage();

        if ($response->status() !== 200) {
            $this->error("Failed to get providers from RA: {$response->status()}");
            return;
        }

        $body = $response->json();

        foreach ($body as $provider) {

            // check if resource already exists
            $resource = \App\Models\Resource::where('meta->external_id', $provider['id'])
                ->where('environment_id', $environmentId)
                ->first();

            if($resource) {

                // update the resource
                $resource->setMeta([
                    'external_id' => $provider['id'],
                    'acuity_id' => $provider['calendar_id'],
                    'email' => $provider['email'],
                    'timezone' => $provider['timezone'],
                ]);
                $resource->save();

                $this->info("Resource already exists: {$resource->name} acuity:{$provider['calendar_id']}. Meta updated.");

            } else {
                $resource = $createResource->create(
                    name: $provider['full_name'],
                    environmentId: $environmentId,
                    active: $provider['enabled'],
                    meta: [
                        'external_id' => $provider['id'],
                        'acuity_id' => $provider['calendar_id'],
                        'email' => $provider['email'],
                        'timezone' => $provider['timezone'],
                    ]
                );

                $this->info("Created resource: {$resource->name} acuity:{$provider['calendar_id']}");
            }

            // update their schedule
            $scheduleData = $this->getScheduleData($provider['schedule']);

            $updateSchedule->execute(
                $resource,
                $scheduleData,
                location: null
            );

        }

    }

    private function getScheduleData($originalData)
    {
        // Original data structure
//        $originalData = [
//            "Monday" => [
//                [
//                    "startTimeHour" => "9",
//                    "startTimeMinute" => "00",
//                    "startTimeSlot" => "am",
//                    "endTimeHour" => "10",
//                    "endTimeMinute" => "00",
//                    "endTimeSlot" => "am"
//                ]
//            ],
//            // Add other days as per your original data structure
//        ];

        // Day of the week mapping to lowercase for new format
        $dayOfWeekMapping = [
            "Monday" => "monday",
            "Tuesday" => "tuesday",
            "Wednesday" => "wednesday",
            "Thursday" => "thursday",
            "Friday" => "friday",
            "Saturday" => "saturday",
            "Sunday" => "sunday",
        ];

        // Process the data
        $newData = ["schedules" => []];
        foreach ($originalData as $day => $timeSlots) {
            $dayLower = $dayOfWeekMapping[$day];
            foreach ($timeSlots as $slot) {
                $newData["schedules"][$dayLower][] = [
                    "start_time" => $this->convertTo24HourFormat((int)$slot['startTimeHour'], (int)$slot['startTimeMinute'], $slot['startTimeSlot']),
                    "end_time" => $this->convertTo24HourFormat((int)$slot['endTimeHour'], (int)$slot['endTimeMinute'], $slot['endTimeSlot'])
                ];
            }
        }

        if (empty($newData['schedules'])) {
            // provide a default empty schedule for each day of the week
            $newData['schedules'] = [
                'monday' => [],
                'tuesday' => [],
                'wednesday' => [],
                'thursday' => [],
                'friday' => [],
                'saturday' => [],
                'sunday' => [],
            ];
        }

        return $newData['schedules'];

    }

    private function convertTo24HourFormat($hour, $minute, $slot)
    {
        $hour = ($slot === 'pm' && $hour != 12) ? $hour + 12 : $hour;
        $hour = ($slot === 'am' && $hour == 12) ? 0 : $hour;
        return sprintf("%02d:%02d:00", $hour, $minute);
    }

    private function getPage()
    {
        $response = \Illuminate\Support\Facades\Http::withHeaders([
            "X-Api-Key" => env('RA_KEY')
        ])->get(env('RA_ENDPOINT')."/api/v1/providers");

        return $response;
    }
}
