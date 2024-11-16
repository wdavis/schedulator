<?php

namespace Tests\Feature\Actions;

use App\Actions\ScopeAvailabilityWithLeadTime;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Period\Boundaries;
use Spatie\Period\Period;
use Spatie\Period\Precision;
use Tests\TestCase;
use App\Actions\GetSchedulesForDate;
use App\Actions\Bookings\GetAllBookings;
use App\Actions\BuildBookingPeriods;
use App\Actions\BuildRecurringSchedule;
use App\Actions\BuildScheduleOverrides;
use App\Actions\GetScheduleOverrides;
use App\Models\Resource;
use App\Models\ScheduleOverride;
use App\Models\Booking;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Spatie\Period\PeriodCollection;

class GetSchedulesForDateTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_schedules_for_date()
    {
        $getScheduleOverridesMock = $this->mock(GetScheduleOverrides::class);
        $buildScheduleOverridesMock = $this->mock(BuildScheduleOverrides::class);
        $buildRecurringScheduleMock = $this->mock(BuildRecurringSchedule::class);
        $getAllBookingsMock = $this->mock(GetAllBookings::class);
        $buildBookingPeriodsMock = $this->mock(BuildBookingPeriods::class);
        $scopeAvailabilityMock = $this->mock(ScopeAvailabilityWithLeadTime::class);

        $getSchedulesForDate = new GetSchedulesForDate(
            $getScheduleOverridesMock,
            $buildScheduleOverridesMock,
            $buildRecurringScheduleMock,
            $getAllBookingsMock,
            $buildBookingPeriodsMock,
            $scopeAvailabilityMock
        );

        $resources = Resource::factory()->count(2)->state(new Sequence(
            [
                'active' => true,
                'booking_window_end_override' => 60
            ],
            ['active' => false]
        ))->create();

        $service = Service::factory()->make([
            'booking_window_end' => 22, // something different than the 60 of the resource
            'duration' => 15,
        ]);

        $startDate = CarbonImmutable::create(2000, 1, 1);
        $endDate = CarbonImmutable::create(2000, 1, 1);

        // Filling in mock expectations and responses according to given return types.
        $getScheduleOverridesMock->shouldReceive('get')
            ->once()
            ->withArgs(function($resourceIds, $startDate, $endDate) use ($resources) {
                $this->assertTrue($resourceIds === [$resources[0]->id]);
                $this->assertTrue($startDate->eq(CarbonImmutable::create(2000, 1, 1)));
                $this->assertTrue($endDate->eq(CarbonImmutable::create(2000, 1, 1)));
                return true;
            })
            ->andReturnUsing(function($resourceIds, $startDate, $endDate) {
                return ScheduleOverride::factory()->count(2)->state(new Sequence(
                    [
                        'resource_id' => $resourceIds[0],
                        'type' => 'opening',
                        'starts_at' => $startDate,
                        'ends_at' => $startDate->addMinutes(15),
                    ],
                    [
                        'resource_id' => $resourceIds[0],
                        'type' => 'block',
                        'starts_at' => $startDate->addMinutes(15),
                        'ends_at' => $startDate->addMinutes(30),
                    ]
                ))->make();
            });

        // Bookings
        $getAllBookingsMock->shouldReceive('get')
            ->once()
            ->andReturnUsing(function($resources, $startDate, $endDate) {
                return Booking::factory()->count(2)->state(new Sequence(
                    [
                        'resource_id' => $resources[0]->id,
                        'starts_at' => $startDate->addMinutes(30),
                        'ends_at' => $startDate->addMinutes(45),
                    ],
                ))->make();
            });

        $buildScheduleOverridesMock->shouldReceive('get')
            ->once()
            ->andReturn([
                // open 8am - 9am
                'opening' => PeriodCollection::make(
                        Period::make(
                            $startDate->hour(8),
                            $startDate->hour(8)->addHour(),
                            Precision::MINUTE(),
                            Boundaries::EXCLUDE_ALL(),
                        )
                    ),
                // block 8 - 8:15
                'block' => PeriodCollection::make(
                        Period::make(
                            $startDate->hour(8),
                            $startDate->hour(8)->addMinutes(15),
                            Precision::MINUTE(),
                            Boundaries::EXCLUDE_ALL(),
                        )
                    )
            ]);

        // recurring schedule 9 to 5
        $buildRecurringScheduleMock->shouldReceive('build')
            ->andReturn(PeriodCollection::make(
                Period::make(
                    $startDate->hour(9),
                    $startDate->hour(17), // 5pm
                    Precision::MINUTE(),
                    Boundaries::EXCLUDE_NONE(),
                )
            ));

        $buildBookingPeriodsMock->shouldReceive('build')
            ->andReturn(PeriodCollection::make(
                Period::make(
                    $startDate->hour(11), // beginning of day
                    $startDate->hour(11)->addMinutes(15),
                    Precision::MINUTE(),
                    Boundaries::EXCLUDE_ALL(),
                )
            ));

        $scopeAvailabilityMock->shouldReceive('scope')
            ->withArgs(function(PeriodCollection $periods, int $leadTime, int $serviceDuration) {
                $this->assertEquals(60, $leadTime);
                $this->assertEquals(15, $serviceDuration);
//                $this->assertTrue($startDate->eq(CarbonImmutable::create(2000, 1, 1)));

                // make sure the periods are correct
//                $this->assertTrue($periods[0]->start()->format('Y-m-d H:i:s') === $startDate->hour(8)->minute(15)->format('Y-m-d H:i:s'));
//                $this->assertTrue($periods[0]->end()->format('Y-m-d H:i:s') === $startDate->hour(11)->format('Y-m-d H:i:s'));

//                $this->assertTrue($periods[1]->start()->format('Y-m-d H:i:s') === $startDate->hour(11)->minute(15)->format('Y-m-d H:i:s'));
//                $this->assertTrue($periods[1]->end()->format('Y-m-d H:i:s') === $startDate->hour(17)->format('Y-m-d H:i:s'));

                return true;
            })
            ->andReturn(PeriodCollection::make(
                Period::make(
                    $startDate->hour(11), // beginning of day
                    $startDate->hour(11)->addMinutes(15),
                    Precision::MINUTE(),
                    Boundaries::EXCLUDE_ALL(),
                )
            ));

        $result = $getSchedulesForDate->get($resources, $service, $startDate, $endDate);

        // make sure active = false Resource's are filtered

        // make sure openings are added
        // todo make sure blocks are removed
        // make sure bookings are removed
        // make sure empty periods are removed

        $this->assertInstanceOf(Collection::class, $result);

        $this->assertCount(1, $result, "Inactive resources should be filtered out");
        $this->assertInstanceOf(Resource::class, $result->first()['resource']);
        $this->assertTrue($result->first()['resource']->id === $resources[0]->id);

        // now lets verify we receive the values from the scopeAvailabilityMock
        $firstResource = $result->first();

        // assert that the first resource has the correct periods
        $this->assertTrue($firstResource['periods'][0]->start()->format('Y-m-d H:i:s') === $startDate->hour(11)->minute(0)->format('Y-m-d H:i:s'));
        $this->assertTrue($firstResource['periods'][0]->end()->format('Y-m-d H:i:s') === $startDate->hour(11)->minute(15)->format('Y-m-d H:i:s'));


    }

    public function test_blocks_are_blocked()
    {
        $getScheduleOverridesMock = $this->mock(GetScheduleOverrides::class);
        $buildScheduleOverridesMock = $this->mock(BuildScheduleOverrides::class);
        $buildRecurringScheduleMock = $this->mock(BuildRecurringSchedule::class);
        $getAllBookingsMock = $this->mock(GetAllBookings::class);
        $buildBookingPeriodsMock = $this->mock(BuildBookingPeriods::class);
        $scopeAvailabilityMock = $this->mock(ScopeAvailabilityWithLeadTime::class);

        $getSchedulesForDate = new GetSchedulesForDate(
            $getScheduleOverridesMock,
            $buildScheduleOverridesMock,
            $buildRecurringScheduleMock,
            $getAllBookingsMock,
            $buildBookingPeriodsMock,
            $scopeAvailabilityMock
        );

        $resources = Resource::factory()->count(2)->state(new Sequence(
            ['active' => true, 'booking_window_end_override' => 60],
            ['active' => false]
        ))->create();

        $service = Service::factory()->make([
            'booking_window_end' => 22, // something different than the 15 of the resource
            'duration' => 15,
        ]);

        $startDate = CarbonImmutable::create(2000, 1, 1);
        $endDate = CarbonImmutable::create(2000, 1, 1);

        // Filling in mock expectations and responses according to given return types.
        $getScheduleOverridesMock->shouldReceive('get')
            ->once()
            ->withArgs(function($resourceIds, $startDate, $endDate) use ($resources) {
                $this->assertTrue($resourceIds === [$resources[0]->id]);
                $this->assertTrue($startDate->eq(CarbonImmutable::create(2000, 1, 1)));
                $this->assertTrue($endDate->eq(CarbonImmutable::create(2000, 1, 1)));
                return true;
            })
            ->andReturnUsing(function($resourceIds, $startDate, $endDate) {
                return ScheduleOverride::factory()->count(2)->state(new Sequence(
                    [
                        'resource_id' => $resourceIds[0],
                        'type' => 'opening',
                        'starts_at' => $startDate,
                        'ends_at' => $startDate->addMinutes(15),
                    ],
                    [
                        'resource_id' => $resourceIds[0],
                        'type' => 'block',
                        'starts_at' => $startDate->addMinutes(15),
                        'ends_at' => $startDate->addMinutes(30),
                    ]
                ))->make();
            });

        // Bookings
        $getAllBookingsMock->shouldReceive('get')
            ->once()
            ->andReturnUsing(function($resources, $startDate, $endDate) {
                return Booking::factory()->count(2)->make();
            });

        $buildScheduleOverridesMock->shouldReceive('get')
            ->once()
            ->andReturn([
                // open 8am - 9am
                'opening' => PeriodCollection::make(
                    Period::make(
                        $startDate->hour(8),
                        $startDate->hour(8)->addHour(),
                        Precision::MINUTE(),
                    )
                ),
                // block 8 - 8:15
                'block' => PeriodCollection::make(
                    Period::make(
                        $startDate->hour(8),
                        $startDate->hour(8)->addMinutes(15),
                        Precision::MINUTE(),
                        Boundaries::EXCLUDE_ALL(),
                    )
                )
            ]);

        $buildRecurringScheduleMock->shouldReceive('build')
            ->andReturn(PeriodCollection::make());

        $buildBookingPeriodsMock->shouldReceive('build')
            ->andReturn(PeriodCollection::make());

        $scopeAvailabilityMock->shouldNotReceive('scope');

        $result = $getSchedulesForDate->get(
            $resources,
            $service,
            $startDate,
            $endDate,
            scopeLeadTimes: false
        );

        // make sure active = false Resource's are filtered

        // make sure openings are added
        // todo make sure blocks are removed
        // make sure bookings are removed
        // make sure empty periods are removed

        $firstResource = $result->first();

//        dd($firstResource);

        // check that start time is 8:15 with a message if not

        // assert that the first resource has the correct periods
        $this->assertTrue($firstResource['periods'][0]->start()->format('Y-m-d H:i:s') === $startDate->hour(8)->minute(15)->format('Y-m-d H:i:s'), "Start time is not 8:15. It is " . $firstResource['periods'][0]->start()->format('Y-m-d H:i:s'));
        $this->assertTrue($firstResource['periods'][0]->end()->format('Y-m-d H:i:s') === $startDate->hour(9)->minute(00)->format('Y-m-d H:i:s'), "End time is not 9:00. It is ".$firstResource['periods'][0]->end()->format('Y-m-d H:i:s'));



    }
}
