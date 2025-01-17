<?php

namespace Tests\Feature;

use App\Actions\SplitPeriodIntoIntervals;
use App\Models\Service;
use Carbon\CarbonImmutable;
use Spatie\Period\Boundaries;
use Spatie\Period\Period;
use Spatie\Period\PeriodCollection;
use Spatie\Period\Precision;
use Tests\TestCase;

class SplitPeriodIntoIntervalsTest extends TestCase
{
    protected SplitPeriodIntoIntervals $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new SplitPeriodIntoIntervals;
    }

    public function test_execute_splits_periods_into_15_minute_intervals()
    {
        // Arrange: Define a 1-hour period and a service with a 15-minute interval
        $periodCollection = new PeriodCollection(
            new Period(
                CarbonImmutable::parse('2024-11-13 08:00'),
                CarbonImmutable::parse('2024-11-13 09:00'),
                Precision::MINUTE(),
                Boundaries::EXCLUDE_NONE()
            )
        );

        $service = Service::factory()->make([
            'duration' => 15, // Set the duration to 15 minutes
        ]);

        // Act
        $result = $this->action->execute($periodCollection, $service);

        // Assert: Check the count and times of split intervals
        $this->assertCount(4, $result);

        $this->assertEquals('2024-11-13 08:00:00', $result[0]['start']->format('Y-m-d H:i:s'));
        $this->assertEquals('2024-11-13 08:15:00', $result[0]['end']->format('Y-m-d H:i:s'));

        $this->assertEquals('2024-11-13 08:15:00', $result[1]['start']->format('Y-m-d H:i:s'));
        $this->assertEquals('2024-11-13 08:30:00', $result[1]['end']->format('Y-m-d H:i:s'));

        $this->assertEquals('2024-11-13 08:30:00', $result[2]['start']->format('Y-m-d H:i:s'));
        $this->assertEquals('2024-11-13 08:45:00', $result[2]['end']->format('Y-m-d H:i:s'));

        $this->assertEquals('2024-11-13 08:45:00', $result[3]['start']->format('Y-m-d H:i:s'));
        $this->assertEquals('2024-11-13 09:00:00', $result[3]['end']->format('Y-m-d H:i:s'));
    }

    public function test_execute_handles_partial_intervals()
    {
        // Arrange: Define a 35-minute period and a service with a 15-minute interval
        $periodCollection = new PeriodCollection(
            new Period(
                CarbonImmutable::parse('2024-11-13 08:00'),
                CarbonImmutable::parse('2024-11-13 08:35'),
                Precision::MINUTE(),
                Boundaries::EXCLUDE_NONE()
            )
        );

        $service = Service::factory()->make([
            'duration' => 15, // Set the duration to 15 minutes
        ]);

        // Act
        $result = $this->action->execute($periodCollection, $service);

        // Assert: Check the count and times of split intervals
        $this->assertCount(3, $result);

        $this->assertEquals('2024-11-13 08:00:00', $result[0]['start']->format('Y-m-d H:i:s'));
        $this->assertEquals('2024-11-13 08:15:00', $result[0]['end']->format('Y-m-d H:i:s'));

        $this->assertEquals('2024-11-13 08:15:00', $result[1]['start']->format('Y-m-d H:i:s'));
        $this->assertEquals('2024-11-13 08:30:00', $result[1]['end']->format('Y-m-d H:i:s'));

        $this->assertEquals('2024-11-13 08:30:00', $result[2]['start']->format('Y-m-d H:i:s'));
        $this->assertEquals('2024-11-13 08:35:00', $result[2]['end']->format('Y-m-d H:i:s')); // Partial interval
    }
}
