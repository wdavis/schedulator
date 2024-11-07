<?php

namespace Tests\Feature\Actions\Bookings;

use App\Actions\Bookings\CreateBooking;
use App\Actions\CheckScheduleAvailability;
use App\Actions\GetCombinedSchedulesForDate;
use App\Actions\ScopeAvailabilityWithLeadTime;
use App\Exceptions\BookingTimeSlotNotAvailableException;
use App\Exceptions\ResourceNotActiveException;
use App\Models\Booking;
use App\Models\Location;
use App\Models\Resource;
use App\Models\Service;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Period\Period;
use Spatie\Period\PeriodCollection;
use Tests\TestCase;

class CreateBookingTest extends TestCase
{
    use RefreshDatabase;

    private $getCombinedSchedulesForDateMock;
    private $checkScheduleAvailabilityMock;
    private $scopeAvailabilityWithLeadTimeMock;
    private $createBookingAction;

    protected function setUp(): void
    {
        parent::setUp();

        $this->getCombinedSchedulesForDateMock = $this->mock(GetCombinedSchedulesForDate::class);
        $this->checkScheduleAvailabilityMock = $this->mock(CheckScheduleAvailability::class);
        $this->scopeAvailabilityWithLeadTimeMock = $this->mock(ScopeAvailabilityWithLeadTime::class);

        $this->createBookingAction = new CreateBooking(
            $this->getCombinedSchedulesForDateMock,
            $this->checkScheduleAvailabilityMock,
            $this->scopeAvailabilityWithLeadTimeMock
        );
    }

    public function test_it_creates_a_booking_successfully()
    {
        $location = Location::factory()->create();
        $resource = Resource::factory()->create(['active' => true]);

        // Attach the location to the resource
        $resource->locations()->attach($location->id);
        $resource->load('location'); // Load the location before passing to CreateBooking

        $service = Service::factory()->create(['duration' => 60]);
        $requestedDate = CarbonImmutable::now()->addDay();

        // Create a PeriodCollection as a mock return value
        $periodCollection = new PeriodCollection(
            Period::make($requestedDate->startOfDay(), $requestedDate->endOfDay())
        );

        $this->getCombinedSchedulesForDateMock
            ->shouldReceive('get')
            ->andReturn($periodCollection);

        $this->checkScheduleAvailabilityMock
            ->shouldReceive('check')
            ->andReturn(true);

        $this->scopeAvailabilityWithLeadTimeMock
            ->shouldReceive('scope')
            ->andReturn($periodCollection);

        $booking = $this->createBookingAction->create(
            $resource->id,
            $service->id,
            $requestedDate->toIso8601String(),
            $resource->environment_id,
            'Test Booking'
        );

        $this->assertInstanceOf(Booking::class, $booking);
        $this->assertEquals($resource->id, $booking->resource_id);
        $this->assertEquals($service->id, $booking->service_id);
        $this->assertEquals($location->id, $booking->location_id);
        $this->assertEquals($requestedDate->toIso8601String(), $booking->starts_at->toIso8601String());
    }

    public function test_it_throws_exception_if_resource_is_not_active()
    {
        $resource = Resource::factory()->create(['active' => false]);
        $service = Service::factory()->create();
        $requestedDate = CarbonImmutable::now()->addDay();

        $this->expectException(ResourceNotActiveException::class);

        $this->createBookingAction->create(
            $resource->id,
            $service->id,
            $requestedDate->toIso8601String(),
            $resource->environment_id
        );
    }

    public function test_it_throws_exception_if_time_slot_not_available()
    {
        $resource = Resource::factory()->create(['active' => true]);
        $service = Service::factory()->create(['duration' => 60]);
        $requestedDate = CarbonImmutable::now()->addDay();

        // Create a PeriodCollection as a mock return value
        $periodCollection = new PeriodCollection(
            Period::make($requestedDate->startOfDay(), $requestedDate->endOfDay())
        );

        // Mock the combined schedule and availability
        $this->getCombinedSchedulesForDateMock
            ->shouldReceive('get')
            ->andReturn($periodCollection);

        $this->checkScheduleAvailabilityMock
            ->shouldReceive('check')
            ->andReturn(false); // Simulate unavailable time slot

        $this->scopeAvailabilityWithLeadTimeMock
            ->shouldReceive('scope')
            ->andReturn($periodCollection);

        $this->expectException(BookingTimeSlotNotAvailableException::class);

        $this->createBookingAction->create(
            $resource->id,
            $service->id,
            $requestedDate->toIso8601String(),
            $resource->environment_id
        );
    }

    public function test_it_bypasses_lead_time_when_allowed()
    {
        $location = Location::factory()->create();
        $resource = Resource::factory()->create(['active' => true]);

        // Attach the location to the resource
        $resource->locations()->attach($location->id);
        $resource->load('location'); // Load the location before passing to CreateBooking

        $service = Service::factory()->create(['duration' => 60]);
        $requestedDate = CarbonImmutable::now()->addDay();

        // Create a PeriodCollection as a mock return value
        $periodCollection = new PeriodCollection(
            Period::make($requestedDate->startOfDay(), $requestedDate->endOfDay())
        );

        // Mock the combined schedule and availability
        $this->getCombinedSchedulesForDateMock
            ->shouldReceive('get')
            ->andReturn($periodCollection);

        $this->checkScheduleAvailabilityMock
            ->shouldReceive('check')
            ->andReturn(true); // Availability before lead time

        $booking = $this->createBookingAction->create(
            $resource->id,
            $service->id,
            $requestedDate->toIso8601String(),
            $resource->environment_id,
            'Test Booking',
            [],
            true // Bypass lead time
        );

        $this->assertInstanceOf(Booking::class, $booking);
        $this->assertEquals($resource->id, $booking->resource_id);
        $this->assertEquals($service->id, $booking->service_id);
        $this->assertTrue($booking->meta['bypassLeadTime']);
    }
}
