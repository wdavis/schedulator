<?php

namespace Tests\Feature\Actions;

use App\Actions\CheckScheduleAvailability;
use App\Actions\GetFirstAvailableResource;
use App\Actions\GetSchedulesForDate;
use App\Exceptions\NoResourceAvailabilityForRequestedTimeException;
use App\Models\Resource;
use App\Models\Service;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Period\Period;
use Spatie\Period\PeriodCollection;
use Tests\TestCase;

class GetFirstAvailableResourceTest extends TestCase
{
    use RefreshDatabase;

    private $get_schedules_for_date;

    private $check_schedule_availability;

    protected function setUp(): void
    {
        parent::setUp();

        $this->get_schedules_for_date = $this->mock(GetSchedulesForDate::class);
        $this->check_schedule_availability = $this->mock(CheckScheduleAvailability::class);
    }

    public function test_get_first_available_resource(): void
    {
        $resources = new Collection;
        $service = Service::factory()->make(['duration' => 15]);
        $requested_date = CarbonImmutable::create(2021, 1, 1, 9, 0, 0);

        $schedules = collect([
            [
                'resource' => Resource::factory()->make(['id' => 1]),
                'resource_id' => 1,
                'periods' => PeriodCollection::make(
                    Period::make($requested_date->setTime(10, 0), $requested_date->setTime(11, 0)),
                ),
            ],
            [
                'resource' => Resource::factory()->make(['id' => 2]),
                'resource_id' => 2,
                'periods' => PeriodCollection::make(
                    Period::make($requested_date->setTime(9, 0), $requested_date->setTime(10, 0)),
                ),
            ],
        ]);

        $this->get_schedules_for_date
            ->shouldReceive('get')
            ->withArgs(function ($passedResources, $passedService, $passedStartDate, $passedEndDate) use ($resources, $requested_date, $service) {
                $this->assertEquals($resources, $passedResources);
                $this->assertEquals($service, $passedService);
                $this->assertEquals($requested_date->startOfDay(), $passedStartDate);
                $this->assertEquals($requested_date->endOfDay(), $passedEndDate);

                return true;
            })
            ->andReturn($schedules);

        $this->check_schedule_availability
            ->shouldReceive('check')
            ->andReturnUsing(function ($passedPeriods, $passedRequestedStartTime, $passedDuration) use ($schedules, $requested_date, $service) {
                static $invocation = 0;
                $invocation++;

                if ($invocation === 1) {
                    $this->assertEquals($schedules[0]['periods'], $passedPeriods);
                    $this->assertEquals($requested_date, $passedRequestedStartTime);
                    $this->assertEquals($service->duration, $passedDuration);

                    return false; // First call returns false
                }

                if ($invocation === 2) {
                    $this->assertEquals($schedules[1]['periods'], $passedPeriods);
                    $this->assertEquals($requested_date, $passedRequestedStartTime);
                    $this->assertEquals($service->duration, $passedDuration);

                    return true; // Second call returns true
                }
            });

        $get_first_available_resource = new GetFirstAvailableResource(
            $this->get_schedules_for_date,
            $this->check_schedule_availability
        );

        $resource_id = $get_first_available_resource->get(
            resources: $resources,
            service: $service,
            requestedDate: $requested_date,
            requestedStartOfDate: $requested_date->startOfDay(),
            requestedEndOfDate: $requested_date->endOfDay()
        );

        $this->assertEquals($schedules[1]['resource']->id, $resource_id);
    }

    public function test_throws_exception_when_no_available_resource(): void
    {
        $this->expectException(NoResourceAvailabilityForRequestedTimeException::class);

        $resources = new Collection;
        $service = Service::factory()->make(['duration' => 15]);
        $requested_date = CarbonImmutable::now();

        $schedules = collect([
            [
                'resource' => Resource::factory()->make(['id' => 1]),
                'resource_id' => 1,
                'periods' => PeriodCollection::make(),
            ],
        ]);

        $this->get_schedules_for_date
            ->shouldReceive('get')
            ->withArgs(function ($passedResources, $passedService, $passedStartDate, $passedEndDate) use ($resources, $requested_date, $service) {
                $this->assertEquals($resources, $passedResources);
                $this->assertEquals($service, $passedService);
                $this->assertEquals($requested_date->startOfDay(), $passedStartDate);
                $this->assertEquals($requested_date->endOfDay(), $passedEndDate);

                return true;
            })
            ->andReturn($schedules);

        $this->check_schedule_availability
            ->shouldReceive('check')
            ->andReturn(false);

        $get_first_available_resource = new GetFirstAvailableResource(
            $this->get_schedules_for_date,
            $this->check_schedule_availability
        );

        $get_first_available_resource->get(
            resources: $resources,
            service: $service,
            requestedDate: $requested_date,
            requestedStartOfDate: $requested_date->startOfDay(),
            requestedEndOfDate: $requested_date->endOfDay()
        );
    }

    // todo test that bookings are subtracted by ensuring the service duration is added to the requested date and passed to get schedules for date
    public function test_end_range_is_generated_based_off_of_the_service_duration(): void
    {
        $resources = new Collection;
        $service = Service::factory()->make(['duration' => 15]);
        $requested_date = CarbonImmutable::now();

        $schedules = collect([
            [
                'resource' => Resource::factory()->make(['id' => 1]),
                'resource_id' => 1,
                'periods' => PeriodCollection::make(),
            ],
        ]);

        $this->get_schedules_for_date
            ->shouldReceive('get')
            ->withArgs(function ($passedResources, $passedService, $passedStartDate, $passedEndDate) use ($resources, $requested_date, $service) {
                $this->assertEquals($resources, $passedResources);
                $this->assertEquals($service, $passedService);
                $this->assertEquals($requested_date->startOfDay(), $passedStartDate);
                $this->assertEquals($requested_date->endOfDay(), $passedEndDate);

                return true;
            })
            ->andReturn($schedules);

        $this->check_schedule_availability
            ->shouldReceive('check')
            ->andReturn(true);

        $get_first_available_resource = new GetFirstAvailableResource(
            $this->get_schedules_for_date,
            $this->check_schedule_availability
        );

        $get_first_available_resource->get(
            resources: $resources,
            service: $service,
            requestedDate: $requested_date,
            requestedStartOfDate: $requested_date->startOfDay(),
            requestedEndOfDate: $requested_date->endOfDay()
        );

    }
}
