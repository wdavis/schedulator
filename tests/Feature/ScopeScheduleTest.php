<?php

namespace Tests\Feature;

use App\Actions\ScopeSchedule;
use Carbon\CarbonImmutable;
use Spatie\Period\Boundaries;
use Spatie\Period\Period;
use Spatie\Period\PeriodCollection;
use Spatie\Period\Precision;
use Tests\TestCase;

class ScopeScheduleTest extends TestCase
{
    protected ScopeSchedule $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new ScopeSchedule;
    }

    public function test_scope_removes_periods_before_start_date()
    {
        // Arrange: Define 15-minute intervals for availability before start date
        $availability = new PeriodCollection(
            new Period(
                CarbonImmutable::parse('2024-11-13 08:00'),
                CarbonImmutable::parse('2024-11-13 08:15'),
                Precision::MINUTE(),
                Boundaries::EXCLUDE_ALL()
            ),
            new Period(
                CarbonImmutable::parse('2024-11-13 08:30'),
                CarbonImmutable::parse('2024-11-13 08:45'),
                Precision::MINUTE(),
                Boundaries::EXCLUDE_ALL()
            )
        );

        $startDate = CarbonImmutable::parse('2024-11-13 08:20');
        $endDate = CarbonImmutable::parse('2024-11-13 09:00');

        // Act
        $result = $this->action->scope($availability, $startDate, $endDate);

        // Assert: Only the second period should remain
        $this->assertCount(1, $result);
        $this->assertTrue($result[0]->startsAfterOrAt($startDate));
    }

    public function test_scope_removes_periods_after_end_date()
    {
        // Arrange: Define availability periods that cross the end date
        $availability = new PeriodCollection(
            new Period(
                CarbonImmutable::parse('2024-11-13 08:30'),
                CarbonImmutable::parse('2024-11-13 09:00'),
                Precision::MINUTE(),
                Boundaries::EXCLUDE_ALL()
            ),
            new Period(
                CarbonImmutable::parse('2024-11-13 09:30'),
                CarbonImmutable::parse('2024-11-13 09:45'),
                Precision::MINUTE(),
                Boundaries::EXCLUDE_ALL()
            )
        );

        $startDate = CarbonImmutable::parse('2024-11-13 08:00');
        $endDate = CarbonImmutable::parse('2024-11-13 09:15');

        // Act
        $result = $this->action->scope($availability, $startDate, $endDate);

        // Assert: Only the first period should remain, clipped to end date
        $this->assertCount(1, $result);
        $this->assertTrue($result[0]->endsBeforeOrAt($endDate));
    }

    public function test_scope_keeps_periods_within_date_range()
    {
        // Arrange: Define periods entirely within the date range
        $availability = new PeriodCollection(
            new Period(
                CarbonImmutable::parse('2024-11-13 08:15'),
                CarbonImmutable::parse('2024-11-13 08:30'),
                Precision::MINUTE(),
                Boundaries::EXCLUDE_ALL()
            ),
            new Period(
                CarbonImmutable::parse('2024-11-13 08:45'),
                CarbonImmutable::parse('2024-11-13 09:00'),
                Precision::MINUTE(),
                Boundaries::EXCLUDE_ALL()
            )
        );

        $startDate = CarbonImmutable::parse('2024-11-13 08:00');
        $endDate = CarbonImmutable::parse('2024-11-13 09:15');

        // Act
        $result = $this->action->scope($availability, $startDate, $endDate);

        // Assert: Both periods should remain within the range
        $this->assertCount(2, $result);
    }

    public function test_scope_excludes_partial_periods_outside_range()
    {
        // Arrange: Define overlapping periods at the edges of the range
        $availability = new PeriodCollection(
            new Period(
                CarbonImmutable::parse('2024-11-13 07:45'),
                CarbonImmutable::parse('2024-11-13 08:00'),
                Precision::MINUTE(),
                Boundaries::EXCLUDE_ALL()
            ),
            new Period(
                CarbonImmutable::parse('2024-11-13 08:15'),
                CarbonImmutable::parse('2024-11-13 08:45'),
                Precision::MINUTE(),
                Boundaries::EXCLUDE_ALL()
            )
        );

        $startDate = CarbonImmutable::parse('2024-11-13 08:00');
        $endDate = CarbonImmutable::parse('2024-11-13 08:30');

        // Act
        $result = $this->action->scope($availability, $startDate, $endDate);
        dump($result);
        // Assert: Only the second period, within range, should remain
        $this->assertCount(1, $result);
        $this->assertTrue($result[0]->startsAfterOrAt($startDate));
        $this->assertTrue($result[0]->endsBeforeOrAt($endDate));
    }
}
