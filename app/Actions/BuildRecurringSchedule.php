<?php

namespace App\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Spatie\Period\Period;
use Spatie\Period\PeriodCollection;
use Spatie\Period\Precision;

/**
 * The reason that we supply both the schedules and the dates are:

 * Schedules: These represent the recurring schedules that you want to apply.
 * Each schedule includes a day of the week and start/end times, which represent a recurring weekly event.
 * For example, a schedule might represent a meeting that occurs every Monday from 9 am to 10 am.

 * Start and End Dates: These dates represent the time range over which you want to apply the schedules.
 * The build method will create periods (start and end timestamps) for each occurrence of each schedule within this date range.
 * The periods will then be combined into a PeriodCollection and returned.

 * The benefit of this approach is that it allows for great flexibility.
 * You could have different sets of schedules for different resources or contexts, and you could apply those schedules over different date ranges as needed.

 * The pre-loading of schedules is important as it allows for efficient bulk creation of periods based on the provided schedules,
 * which could be advantageous in terms of performance especially when dealing with large sets of data.
 * By supplying the schedules upfront, the build method can iterate through them once and create all the periods at once.

 * The inclusion of startDate and endDate gives you the control to define the exact date range for which you want to generate the recurring schedule,
 * making the build method more flexible and reusable in different contexts. This is helpful because in some scenarios you might want to generate a
 * schedule for the next week, the next month, the next year, and so forth, all of which could be achieved by changing the startDate and endDate parameters.
 */
class BuildRecurringSchedule
{
    public function build(Collection $schedules, CarbonImmutable $startDate, CarbonImmutable $endDate): PeriodCollection
    {
        $itemsToAdd = [];

        foreach ($schedules as $schedule) {

            $currentDate = $startDate->copy();

            while ($currentDate->dayOfWeekIso !== $schedule->day_of_week) {
                $currentDate = $currentDate->addDay();
            }

            while ($currentDate->lte($endDate)) {
                $start = $currentDate->setTimeFromTimeString($schedule->start_time);
                $end = $currentDate->setTimeFromTimeString($schedule->end_time);

                $itemsToAdd[] = Period::make($start, $end, Precision::MINUTE());

                $currentDate = $currentDate->addWeek();
            }
        }

//        return (new PeriodCollection(...$itemsToAdd))->overlapAll();
        return (new PeriodCollection(...$itemsToAdd))->union();
    }

}
