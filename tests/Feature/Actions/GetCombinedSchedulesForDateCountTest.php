<?php

namespace Tests\Feature\Actions;

use App\Actions\GetCombinedSchedulesForDateCount;
use App\Actions\GetSchedulesForDate;
use App\Models\Resource;
use App\Models\Service;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Period\Boundaries;
use Spatie\Period\Precision;
use Tests\TestCase;

class GetCombinedSchedulesForDateCountTest extends TestCase
{
    use RefreshDatabase;

    protected GetCombinedSchedulesForDateCount $action;

    protected GetSchedulesForDate $getSchedulesForDateMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->getSchedulesForDateMock = $this->mock(GetSchedulesForDate::class);
        $this->action = new GetCombinedSchedulesForDateCount($this->getSchedulesForDateMock);
    }

    public function test_get_combined_schedules_for_single_resource_and_period()
    {
        // Arrange: Create resources, service, and set up the schedule data
        $resource = Resource::factory()->create();
        $service = Service::factory()->create(['duration' => 15]);

        $startDate = CarbonImmutable::now()->startOfDay();
        $endDate = $startDate->addDay();

        $scheduleData = collect([
            [
                'resource' => $resource,
                'periods' => [
                    new \Spatie\Period\Period(
                        $startDate->setTime(9, 0),
                        $startDate->setTime(12, 0),
                        Precision::MINUTE(),
                        Boundaries::EXCLUDE_ALL()
                    ),
                ],
            ],
        ]);

        $this->getSchedulesForDateMock
            ->shouldReceive('get')
            ->once()
            ->withArgs(function ($resources) use ($resource) {
                return $resources->contains($resource);
            })
            ->andReturn($scheduleData);

        // Act: Call the action
        $result = $this->action->get(new \Illuminate\Database\Eloquent\Collection([$resource]), $service, $startDate, $endDate);

        // Assert: Verify that the expected number of slots is calculated
        $expectedSlots = 3 * 4; // 3 hours with 4 slots per hour at 15 minutes each
        $this->assertEquals($expectedSlots, $result);
    }

    public function test_get_combined_schedules_for_multiple_resources()
    {
        // Arrange: Create multiple resources, a service, and define their periods
        $resource1 = Resource::factory()->create();
        $resource2 = Resource::factory()->create();
        $service = Service::factory()->create(['duration' => 30]);

        $startDate = CarbonImmutable::now()->startOfDay();
        $endDate = $startDate->addDay();

        $scheduleData = collect([
            [
                'resource' => $resource1,
                'periods' => [
                    new \Spatie\Period\Period(
                        $startDate->setTime(10, 0),
                        $startDate->setTime(11, 0),
                        Precision::MINUTE(),
                        Boundaries::EXCLUDE_ALL()
                    ),
                ],
            ],
            [
                'resource' => $resource2,
                'periods' => [
                    new \Spatie\Period\Period(
                        $startDate->setTime(10, 0),
                        $startDate->setTime(12, 0),
                        Precision::MINUTE(),
                        Boundaries::EXCLUDE_ALL()
                    ),
                ],
            ],
        ]);

        $this->getSchedulesForDateMock
            ->shouldReceive('get')
            ->once()
            ->withArgs(function ($resources) use ($resource1, $resource2) {
                return $resources->contains($resource1) && $resources->contains($resource2);
            })
            ->andReturn($scheduleData);

        // Act
        $result = $this->action->get(new \Illuminate\Database\Eloquent\Collection([$resource1, $resource2]), $service, $startDate, $endDate);

        // Assert
        $expectedSlots = 2 + 4; // resource1 has 2 slots (1 hour at 30 mins each), resource2 has 4 slots (2 hours at 30 mins each)
        $this->assertEquals($expectedSlots, $result);
    }

    public function test_no_schedules_returns_zero_slots()
    {
        // Arrange: Set up resources and service but no available periods
        $resource = Resource::factory()->create();
        $service = Service::factory()->create(['duration' => 15]);

        $startDate = CarbonImmutable::now()->startOfDay();
        $endDate = $startDate->addDay();

        $this->getSchedulesForDateMock
            ->shouldReceive('get')
            ->once()
            ->andReturn(collect([
                [
                    'resource' => $resource,
                    'periods' => [],
                ],
            ]));

        // Act
        $result = $this->action->get(new \Illuminate\Database\Eloquent\Collection([$resource]), $service, $startDate, $endDate);

        // Assert
        $this->assertEquals(0, $result);
    }

    public function test_partial_periods_count_correctly()
    {
        // Arrange: Create resources, service, and set partial period times
        $resource = Resource::factory()->create();
        $service = Service::factory()->create(['duration' => 20]);

        $startDate = CarbonImmutable::now()->startOfDay();
        $endDate = $startDate->addDay();

        $scheduleData = collect([
            [
                'resource' => $resource,
                'periods' => [
                    new \Spatie\Period\Period(
                        $startDate->setTime(14, 00),
                        $startDate->setTime(15, 40),
                        Precision::MINUTE(),
                        Boundaries::EXCLUDE_ALL()
                    ),
                ],
            ],
        ]);

        $this->getSchedulesForDateMock
            ->shouldReceive('get')
            ->once()
            ->andReturn($scheduleData);

        // Act
        $result = $this->action->get(new \Illuminate\Database\Eloquent\Collection([$resource]), $service, $startDate, $endDate);

        // Assert
        $expectedSlots = 5; // approx. 1 hr 40 mins with 20 mins per slot
        $this->assertEquals($expectedSlots, $result);
    }
}
