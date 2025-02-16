<?php

namespace Tests\Feature\Actions;

use App\Actions\CombinePeriodCollections;
use App\Actions\GetCombinedSchedulesForDate;
use App\Actions\GetSchedulesForDate;
use App\Models\Resource;
use App\Models\Service;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Period\PeriodCollection;
use Tests\TestCase;

class GetCombinedSchedulesForDateTest extends TestCase
{
    private $getSchedulesForDateMock;

    private $combinePeriodCollectionsMock;

    private $getCombinedSchedulesForDate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->getSchedulesForDateMock = $this->mock(GetSchedulesForDate::class);
        $this->combinePeriodCollectionsMock = $this->mock(CombinePeriodCollections::class);

        $this->getCombinedSchedulesForDate = new GetCombinedSchedulesForDate(
            $this->getSchedulesForDateMock,
            $this->combinePeriodCollectionsMock
        );
    }

    public function test_get_returns_combined_schedule_for_given_date_range(): void
    {
        // Arrange
        $resources = Collection::make([new Resource(['id' => 'resource1']), new Resource(['id' => 'resource2'])]);
        $service = new Service(['duration' => 30]);
        $startDate = CarbonImmutable::now();
        $endDate = CarbonImmutable::now()->addDays(1);

        // Mock the GetSchedulesForDate behavior
        $expectedSchedules = new Collection([
            new PeriodCollection,
            new PeriodCollection,
        ]);
        $this->getSchedulesForDateMock
            ->shouldReceive('get')
            ->once()
            ->withArgs(function ($passedResources, $passedService, $passedStartDate, $passedEndDate) use ($resources, $service, $startDate, $endDate) {
                $this->assertEquals($resources, $passedResources);
                $this->assertEquals($service, $passedService);
                $this->assertEquals($startDate, $passedStartDate);
                $this->assertEquals($endDate, $passedEndDate);

                return true;
            })
            ->andReturn($expectedSchedules);

        // Mock the CombinePeriodCollections behavior
        $combinedPeriodCollection = new PeriodCollection;
        $this->combinePeriodCollectionsMock
            ->shouldReceive('combine')
            ->once()
            ->withArgs(function ($passedSchedules, $key) use ($expectedSchedules) {
                $this->assertEquals($expectedSchedules, $passedSchedules);
                $this->assertEquals('periods', $key);

                return true;
            })
            ->andReturn($combinedPeriodCollection);

        // Act
        $result = $this->getCombinedSchedulesForDate->get($resources, $service, $startDate, $endDate);

        // Assert
        $this->assertInstanceOf(PeriodCollection::class, $result);
        $this->assertSame($combinedPeriodCollection, $result);
    }

    public function test_get_with_empty_resources_returns_empty_collection(): void
    {
        // Arrange
        $resources = Collection::make([]);
        $service = new Service;
        $startDate = CarbonImmutable::now();
        $endDate = CarbonImmutable::now()->addDays(1);

        $this->getSchedulesForDateMock
            ->shouldReceive('get')
            ->once()
            ->withArgs(function ($passedResources, $passedService, $passedStartDate, $passedEndDate) use ($resources, $service, $startDate, $endDate) {
                $this->assertEquals($resources, $passedResources);
                $this->assertEquals($service, $passedService);
                $this->assertEquals($startDate, $passedStartDate);
                $this->assertEquals($endDate, $passedEndDate);

                return true;
            })->andReturn(collect());

        $this->combinePeriodCollectionsMock
            ->shouldReceive('combine')
            ->once()
            ->withArgs(function ($passedSchedules, $key) {
                $this->assertEquals(collect(), $passedSchedules);
                $this->assertEquals('periods', $key);

                return true;
            })->andReturn(new PeriodCollection);

        // Act
        $result = $this->getCombinedSchedulesForDate->get($resources, $service, $startDate, $endDate);

        // Assert
        $this->assertInstanceOf(PeriodCollection::class, $result);
        $this->assertCount(0, $result); // Expecting an empty PeriodCollection
    }

    public function test_get_combines_periods_correctly_for_multiple_resources(): void
    {
        // Arrange
        $resources = Collection::make([new Resource, new Resource]);
        $service = new Service(['duration' => 45]);
        $startDate = CarbonImmutable::now();
        $endDate = CarbonImmutable::now()->addDays(2);

        // Mock individual schedules for each resource
        $individualSchedules = new Collection([
            new PeriodCollection,
            new PeriodCollection,
        ]);
        $this->getSchedulesForDateMock
            ->shouldReceive('get')
            ->once()
            ->withArgs(function ($passedResources, $passedService, $passedStartDate, $passedEndDate) use ($resources, $service, $startDate, $endDate) {
                $this->assertEquals($resources, $passedResources);
                $this->assertEquals($service, $passedService);
                $this->assertEquals($startDate, $passedStartDate);
                $this->assertEquals($endDate, $passedEndDate);

                return true;
            })
            ->andReturn($individualSchedules);

        // Mock combined period collection response
        $combinedPeriods = new PeriodCollection;
        $this->combinePeriodCollectionsMock
            ->shouldReceive('combine')
            ->once()
            ->withArgs(function ($passedSchedules, $key) use ($individualSchedules) {
                $this->assertEquals($individualSchedules, $passedSchedules);
                $this->assertEquals('periods', $key);

                return true;
            })
            ->andReturn($combinedPeriods);

        // Act
        $result = $this->getCombinedSchedulesForDate->get($resources, $service, $startDate, $endDate);

        // Assert
        $this->assertSame($combinedPeriods, $result);
    }

    public function test_get_with_null_schedule_returns_empty_period_collection(): void
    {
        // Arrange
        $resources = Collection::make([new Resource]);
        $service = new Service(['duration' => 60]);
        $startDate = CarbonImmutable::now();
        $endDate = CarbonImmutable::now()->addDays(1);

        // Simulate getSchedulesForDate returning an empty collection
        $this->getSchedulesForDateMock
            ->shouldReceive('get')
            ->once()
            ->andReturn(collect());

        // CombinePeriodCollections should return an empty PeriodCollection when no schedules are passed
        $this->combinePeriodCollectionsMock
            ->shouldReceive('combine')
            ->once()
            ->withArgs(function ($passedSchedules, $key) {
                $this->assertEquals(collect(), $passedSchedules);
                $this->assertEquals('periods', $key);

                return true;
            })
            ->andReturn(new PeriodCollection);

        // Act
        $result = $this->getCombinedSchedulesForDate->get($resources, $service, $startDate, $endDate);

        // Assert
        $this->assertInstanceOf(PeriodCollection::class, $result);
        $this->assertCount(0, $result); // Expecting an empty PeriodCollection
    }
}
