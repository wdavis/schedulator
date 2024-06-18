<?php

namespace Tests\Feature\Actions;

use App\Actions\GetOpeningsCountPerDay;
use App\Models\Resource;
use App\Models\Service;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Period\Period;
use Spatie\Period\PeriodCollection;
use Tests\TestCase;
use Illuminate\Support\Collection;

class GetOpeningsCountPerDayTest extends TestCase
{
    public function test_can_get_openings_count_per_day()
    {
        // Using factories to generate models
        $resources = Resource::factory()->count(1)->make(); // You can add logic to generate a collection of resources using factories
        $service = Service::factory()->make(['id' => 1]);
        $startDate = CarbonImmutable::parse('2023-08-12');
        $endDate = CarbonImmutable::parse('2023-08-14');

        $periods = new PeriodCollection(
            Period::make(CarbonImmutable::parse('2023-08-12 10:00'), CarbonImmutable::parse('2023-08-12 11:00')),
            Period::make(CarbonImmutable::parse('2023-08-12 12:00'), CarbonImmutable::parse('2023-08-12 13:00')),
        );

        $getSchedulesForDateMock = $this->mock(\App\Actions\GetSchedulesForDate::class, function ($mock) use ($resources, $service, $startDate, $endDate, $periods) {
            $mock->shouldReceive('get')
                ->withArgs(function ($resources, $service, $startDate, $endDate, $scopeLeadTimes) {
                    $this->assertEquals($resources->count(), 1);
                    $this->assertEquals($service->id, 1);
                    $this->assertEquals($startDate->toDateString(), '2023-08-12');
                    $this->assertEquals($endDate->toDateString(), '2023-08-14');
                    $this->assertEquals($scopeLeadTimes, false);

                    return true;
                })
                ->andReturn(collect(
                    [
                        [
                            'resource' => Resource::factory()->make(['id' => 1]),
//                        'location_id' => 1,
//                        'location' => Location,
                            'periods' => $periods
                        ]
                    ]
                ));
        });

        $splitPeriodIntoIntervalsMock = $this->mock(\App\Actions\SplitPeriodIntoIntervals::class, function ($mock) use ($resources, $service, $startDate, $endDate, $periods) {
            $mock->shouldReceive('execute')
                ->once()
                ->withArgs(function ($periods, $service) {
                    $this->assertEquals($periods->count(), 2);
                    $this->assertEquals($service->id, 1);

                    return true;
                })
                ->andReturn([
                    [
                        'start' => CarbonImmutable::parse('2023-08-12 10:00'),
                        'end' => CarbonImmutable::parse('2023-08-12 11:00'),
                    ],
                    [
                        'start' => CarbonImmutable::parse('2023-08-12 12:00'),
                        'end' => CarbonImmutable::parse('2023-08-12 13:00'),
                    ],
                ]);
        });

        // run
        $getOpeningsCountPerDay = new GetOpeningsCountPerDay($getSchedulesForDateMock, $splitPeriodIntoIntervalsMock);
        $result = $getOpeningsCountPerDay->get($resources, $service, $startDate, $endDate);

        // Assert the specific values based on your expectations
        $this->assertIsArray($result);

        // You can add more specific assertions here, such as:
        $this->assertEquals(2, $result['2023-08-12']['count']);
        $this->assertEquals(1, $result['2023-08-12']['slotsByHour']['10:00:00-11:00:00']['count']);
        $this->assertEquals(1, $result['2023-08-12']['slotsByHour']['12:00:00-13:00:00']['count']);

        // todo not quite sure how to test the colors yet
//        $this->assertEquals(null, $result['2023-08-12']['color']);
    }

}
