<?php

namespace Tests\Unit;

use App\Actions\BuildRecurringSchedule;
use App\Models\Schedule;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Period\Period;
use Spatie\Period\PeriodCollection;
use Tests\TestCase;

class BuildRecurringScheduleTest extends TestCase
{
    public function test_build_method()
    {
        $startDate = CarbonImmutable::create(2021, 1, 1, 0, 0, 0);

        $this->travelTo($startDate);

        // Create a new instance of BuildRecurringSchedule
        $buildRecurringSchedule = new BuildRecurringSchedule();

        // Define start and end date
        $endDate = $startDate->addWeek();

        // Create some schedules
        $schedules = new Collection([
            new Schedule([
                'day_of_week' => 1,
                'start_time' => '08:00',
                'end_time' => '12:00',
            ]),
            new Schedule([
                'day_of_week' => 2,
                'start_time' => '13:00',
                'end_time' => '17:00',
            ]),
        ]);

        // Call build method
        $result = $buildRecurringSchedule->build($schedules, $startDate, $endDate)->sort();

        // Assert the result is a PeriodCollection
        $this->assertInstanceOf(PeriodCollection::class, $result);

        // Assert the periods in the collection are correct
        $this->assertCount(2, $result);

        // make sure date and time are correct for first period
        $this->assertEquals('2021-01-04 08:00', $result[0]->start()->format('Y-m-d H:i'));
        $this->assertEquals('2021-01-04 12:00', $result[0]->end()->format('Y-m-d H:i'));

        $this->assertEquals('2021-01-05 13:00', $result[1]->start()->format('Y-m-d H:i'));
        $this->assertEquals('2021-01-05 17:00', $result[1]->end()->format('Y-m-d H:i'));


    }
}
