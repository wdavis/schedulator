<?php

namespace Tests\Unit\Actions;

use App\Actions\CheckScheduleAvailability;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;
use Spatie\Period\Boundaries;
use Spatie\Period\Period;
use Spatie\Period\PeriodCollection;
use Spatie\Period\Precision;

class CheckScheduleAvailabilityTest extends TestCase
{
    protected CheckScheduleAvailability $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new CheckScheduleAvailability;
    }

    public function test_it_returns_true_when_request_falls_within_availability(): void
    {
        $availability = new PeriodCollection(
            Period::make(
                CarbonImmutable::parse('2024-11-13 10:00:00'),
                CarbonImmutable::parse('2024-11-13 12:00:00'),
                Precision::MINUTE()
            ),
        );

        $requestedStartTime = CarbonImmutable::parse('2024-11-13 10:30:00');
        $result = $this->action->check($availability, $requestedStartTime, duration: 30);

        $this->assertTrue($result);
    }

    public function test_it_returns_false_when_request_is_outside_availability(): void
    {
        $availability = new PeriodCollection(
            Period::make(
                CarbonImmutable::parse('2024-11-13 10:00:00'),
                CarbonImmutable::parse('2024-11-13 12:00:00'),
                Precision::MINUTE()
            ),
        );

        $requestedStartTime = CarbonImmutable::parse('2024-11-13 09:00:00');
        $result = $this->action->check($availability, $requestedStartTime, duration: 30);

        $this->assertFalse($result);
    }

    public function test_it_returns_false_when_request_partially_overlaps_availability(): void
    {
        $availability = new PeriodCollection(
            Period::make(
                CarbonImmutable::parse('2024-11-13 10:00:00'),
                CarbonImmutable::parse('2024-11-13 12:00:00'),
                Precision::MINUTE()
            ),
        );

        $requestedStartTime = CarbonImmutable::parse('2024-11-13 11:45:00');
        $result = $this->action->check($availability, $requestedStartTime, duration: 30);

        $this->assertFalse($result);
    }

    public function test_it_handles_buffer_before_correctly(): void
    {
        $availability = new PeriodCollection(
            Period::make(
                CarbonImmutable::parse('2024-11-13 10:00:00'),
                CarbonImmutable::parse('2024-11-13 12:00:00'),
                Precision::MINUTE()
            ),
        );

        $requestedStartTime = CarbonImmutable::parse('2024-11-13 10:15:00');
        $result = $this->action->check($availability, $requestedStartTime, duration: 30, bufferBefore: 15);

        $this->assertTrue($result);
    }

    public function test_it_returns_true_for_15_minute_window_within_availability(): void
    {
        $availability = new PeriodCollection(
            Period::make(
                CarbonImmutable::parse('2024-11-13 10:00:00'),
                CarbonImmutable::parse('2024-11-13 10:15:00'),
                Precision::MINUTE(),
                //                Boundaries::EXCLUDE_ALL()
            ),
        );

        $requestedStartTime = CarbonImmutable::parse('2024-11-13 10:00:00');
        $result = $this->action->check($availability, $requestedStartTime, duration: 15);

        $this->assertTrue($result, 'Expected the availability to contain the requested period');
    }
}
