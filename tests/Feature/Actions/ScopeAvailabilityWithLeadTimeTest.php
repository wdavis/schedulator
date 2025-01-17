<?php

namespace Tests\Feature\Actions;

use App\Actions\AdjustTimeInterval;
use App\Actions\ScopeAvailabilityWithLeadTime;
use Carbon\CarbonImmutable;
use Spatie\Period\Boundaries;
use Spatie\Period\Period;
use Spatie\Period\PeriodCollection;
use Spatie\Period\Precision;
use Tests\TestCase;

class ScopeAvailabilityWithLeadTimeTest extends TestCase
{
    public function test_scope_method(): void
    {
        // Create an instance of ScopeAvailabilityWithLeadTime
        $scoper = new ScopeAvailabilityWithLeadTime(new AdjustTimeInterval);

        // Create a PeriodCollection for testing
        $start = CarbonImmutable::create(2022, 01, 01);
        $end = $start->addHours(2);
        $period = new Period($start, $end, Precision::MINUTE(), Boundaries::EXCLUDE_NONE());
        $availability = new PeriodCollection($period);

        // Parameters for the scope method
        $leadTimeInMinutes = 30;
        $bookingDurationInMinutes = 15;

        // Call the scope method
        $result = $scoper->scope($availability, $leadTimeInMinutes, $bookingDurationInMinutes, $start);

        // Check that the result is a PeriodCollection
        $this->assertInstanceOf(PeriodCollection::class, $result);

        // Check that the result has the expected number of periods
        $this->assertCount(1, $result);

        // Check that the result's period has the expected start and end times
        $expectedStart = $start->addMinutes($leadTimeInMinutes + $bookingDurationInMinutes);
        $this->assertEquals($expectedStart, $result->current()->start());
        $this->assertEquals($end, $result->current()->end());
    }

    /**
     * Test the scope method with a 24-hour lead time.
     *
     * @return void
     */
    public function test_scope_method_with_24_hour_lead_time(): void
    {
        // Create an instance of ScopeAvailabilityWithLeadTime
        $scoper = new ScopeAvailabilityWithLeadTime(new AdjustTimeInterval);

        // Create a PeriodCollection for testing
        $start = CarbonImmutable::create(2022, 01, 01);
        $end = $start->addHours(32);
        $period = new Period($start->subHours(12), $end, Precision::MINUTE(), Boundaries::EXCLUDE_NONE());
        $availability = new PeriodCollection($period);

        // Parameters for the scope method
        $leadTimeInMinutes = 1440; // 24 hours
        $bookingDurationInMinutes = 15;

        // Call the scope method
        $result = $scoper->scope($availability, $leadTimeInMinutes, $bookingDurationInMinutes, $start);

        // Check that the result is a PeriodCollection
        $this->assertInstanceOf(PeriodCollection::class, $result);

        // make sure the period collection does not contain the first 24 hours
        $this->assertFalse($result->current()->contains($start->subHours(6)));

        // make sure the period starts at the expected time, 24 hours after the requested start time
        $this->assertTrue($result->current()->start()->format('Y-m-d H:i:s') === $start->addHours(24)->minute(15)->format('Y-m-d H:i:s'));
    }
}
