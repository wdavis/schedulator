<?php

namespace Tests\Feature\Actions;

use App\Actions\BuildBookingPeriods;
use App\Models\Booking;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Period\Boundaries;
use Spatie\Period\Period;
use Spatie\Period\PeriodCollection;
use Spatie\Period\Precision;
use Tests\TestCase;

class BuildBookingPeriodsTest extends TestCase
{
    /** @test */
    public function test_build_creates_period_collection_with_provided_bookings()
    {
        $booking1 = Booking::factory()->make([
            'starts_at' => CarbonImmutable::now()->setTime(10, 0),
            'ends_at' => CarbonImmutable::now()->setTime(11, 0),
        ]);

        $booking2 = Booking::factory()->make([
            'starts_at' => CarbonImmutable::now()->setTime(12, 0),
            'ends_at' => CarbonImmutable::now()->setTime(13, 0),
        ]);

        $action = new BuildBookingPeriods();
        $periods = $action->build(new \Illuminate\Database\Eloquent\Collection([$booking1, $booking2]));

        $this->assertInstanceOf(PeriodCollection::class, $periods);
        $this->assertCount(2, $periods);
    }

    /** @test */
    public function test_build_creates_periods_with_correct_boundaries()
    {
        $booking = Booking::factory()->make([
            'starts_at' => CarbonImmutable::now()->setTime(10, 0),
            'ends_at' => CarbonImmutable::now()->setTime(11, 0),
        ]);

        $action = new BuildBookingPeriods();
        $periods = $action->build(new \Illuminate\Database\Eloquent\Collection([$booking]));

        $this->assertEquals(Boundaries::EXCLUDE_ALL(), $periods[0]->boundaries());
    }

    /** @test */
    public function test_build_creates_periods_with_minute_precision()
    {
        $booking = Booking::factory()->make([
            'starts_at' => CarbonImmutable::now()->setTime(10, 0),
            'ends_at' => CarbonImmutable::now()->setTime(11, 0),
        ]);

        $action = new BuildBookingPeriods();
        $periods = $action->build(new \Illuminate\Database\Eloquent\Collection([$booking]));

        $this->assertEquals(Precision::MINUTE(), $periods[0]->precision());
    }

}
