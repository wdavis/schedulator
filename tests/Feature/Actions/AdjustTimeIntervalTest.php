<?php

namespace Tests\Feature\Actions;

use App\Actions\AdjustTimeInterval;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AdjustTimeIntervalTest extends TestCase
{
    private AdjustTimeInterval $adjustTimeInterval;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adjustTimeInterval = new AdjustTimeInterval();
    }

    public function test_adjust_with_bump_to_next_interval(): void
    {
        $time = CarbonImmutable::create(2023, 7, 2, 10, 5, 0);
        $interval = 15;

        $expectedTime = CarbonImmutable::create(2023, 7, 2, 10, 15, 0);

        $result = $this->adjustTimeInterval->adjust($time, $interval, bumpToNextInterval: true);

        $this->assertEquals($expectedTime, $result);
    }

    public function test_adjust_without_bump_to_next_interval(): void
    {
        $time = CarbonImmutable::create(2023, 7, 2, 10, 0, 0);
        $interval = 15;

        $expectedTime = CarbonImmutable::create(2023, 7, 2, 10, 0, 0);

        $result = $this->adjustTimeInterval->adjust($time, $interval, bumpToNextInterval: false);

        $this->assertEquals($expectedTime, $result);
    }

    public function test_carry_over_to_next_hour(): void
    {
        $time = CarbonImmutable::create(2023, 7, 2, 10, 45, 0);
        $interval = 15;

        $expectedTime = CarbonImmutable::create(2023, 7, 2, 11, 0, 0);

        $result = $this->adjustTimeInterval->adjust($time, $interval, bumpToNextInterval: true);

        $this->assertEquals($expectedTime, $result);
    }
}
