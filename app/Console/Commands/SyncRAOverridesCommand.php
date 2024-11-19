<?php

namespace App\Console\Commands;

use App\Actions\BuildOverridesForDay;
use App\Actions\Imports\AcuityRequest;
use App\Actions\Imports\DailyHours;
use App\Actions\Overrides\CreateOverride;
use App\Enums\ScheduleOverrideType;
use App\Models\Environment;
use App\Models\Resource;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class SyncRAOverridesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ra:sync-overrides {environmentId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(BuildOverridesForDay $buildOverridesForDay, CreateOverride $createOverride)
    {
        $environmentId = $this->argument('environmentId');

        $environment = Environment::where('id', $environmentId)
            ->with('services')
            ->firstOrFail();

        $this->info("Syncing appointments for environment: {$environment->name}");

        $monthStrings = [];
        $monthsForward = 1;

        $currentDate = CarbonImmutable::now();
        while ($monthsForward > 0) {
            $monthStrings[] = $currentDate->format('Y-m-d');
            $currentDate = $currentDate->addMonth();
            $monthsForward--;
        }

        // get resources that have acuity_id
        $resources = Resource::whereNotNull('meta->acuity_id')
            ->where('environment_id', $environmentId)
            ->get();

        foreach($resources as $resource) {
            foreach ($monthStrings as $monthString) {

                $date = CarbonImmutable::parse($monthString);

                $body = $this->getOverrideScheduleHours($resource->getMeta('acuity_id'), $date);

                echo(json_encode($body) . PHP_EOL);

                $this->info("Processing {$resource->name} for {$date->format('Y-m-d')}");

                $something = $body->filter(function(DailyHours $override) use ($resource) {
                    return $override->overridden; // check if the hours are overridden for this date
                })->map(function (DailyHours $override) use ($resource) {

                    $hours = collect($override->hours)->map(function ($hour) use ($override, $resource) {
                        $startTime = CarbonImmutable::parse(CarbonImmutable::parse($override->date)->format('Y-m-d') . ' ' . $hour->startTimeHour . ':' . $hour->startTimeMinute . ' ' . $hour->startTimeSlot, $resource->getMeta('timezone'));
                        $updatedStartTime = $startTime->setTimezone('utc');
                        $endTime = CarbonImmutable::parse(CarbonImmutable::parse($override->date)->format('Y-m-d') . ' ' . $hour->endTimeHour . ':' . $hour->endTimeMinute . ' ' . $hour->endTimeSlot, $resource->getMeta('timezone'));
                        $updatedEndTime = $endTime->setTimezone('utc');

                        return [
                            'resource_starts_at' => $startTime->toIso8601String(),
                            'starts_at' => $updatedStartTime->toIso8601String(),
                            'resource_ends_at' => $endTime->toIso8601String(),
                            'ends_at' => $updatedEndTime->toIso8601String()
                        ];
                    });

                    return $hours;
                });

                if($something->isEmpty()) {
                    continue;
                }

                $overridesForDay = $buildOverridesForDay->build($date, $something);
//
                // create the overrides

                foreach($overridesForDay['opening'] as $override) {
                    $createOverride->create(
                        $resource,
                        ScheduleOverrideType::opening,
                        CarbonImmutable::parse($override['starts_at']),
                        CarbonImmutable::parse($override['ends_at'])
                    );
                }
//{
//    "overridden": true,
//    "date": "2023-11-25",
//    "hours": [
//        {
//            "startTimeHour": "9",
//            "startTimeMinute": "00",
//            "startTimeSlot": "am",
//            "endTimeHour": "10",
//            "endTimeMinute": "00",
//            "endTimeSlot": "am"
//        }
//    ],
//},
//return $override;
                print_r($overridesForDay);

                // todo will have to look at regular schedule and diff the opening and slice the overrides into blocks

                //
            }
        }
    }

    public function getOverrideScheduleHours(int $calendarId, CarbonImmutable $date): Collection
    {
        $ar = new AcuityRequest();
        $hoursOp = $ar->get("/app/v1/calendars/groups/{$calendarId}/override-hours/{$date->format('Y-m-d')}");

        $data = collect($hoursOp);

        $currentDate = Carbon::now()->setTime(0, 0, 0); // todo timezones?

        // pad beginning
        // start day on calendar
        // dayOfWeekIso 1-7
        $startDay = 1;
        $monthStartingDay = $date->dayOfWeekIso;
        $beginningDifference = $monthStartingDay - $startDay;

        $beginning = [];

        for ($i = $beginningDifference; $i >= $startDay; $i--) {
            $bDate = $date->subDays($i);
            $beginning[] = new DailyHours($bDate->format('Y-m-d'), [], false, true);
        }

        $mapped = $data->map(function ($day) use ($currentDate) {
            // $day->id has an id in acuity if the date's hours have been overridden, otherwise it is false

            return new DailyHours($day->currentDate, $day->hours, $currentDate->gt(Carbon::create($day->currentDate)), false, $day->id !== false);
        });

        // pad end
        $endDay = 7;
        $monthEndDay = $date->addMonth()->subDay()->dayOfWeekIso;

        $endingDifference = $endDay - $monthEndDay;

        $ending = [];

        for ($i = 1; $i <= $endingDifference; $i++) {
            $bDate = $date->addMonth()->subDay()->addDays($i);
            $ending[] = new DailyHours($bDate->format('Y-m-d'), [], false, true);
        }

        return collect(array_merge(array_values($beginning), $mapped->values()->toArray(), array_values($ending)));
    }
}
