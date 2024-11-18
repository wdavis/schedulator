<?php

namespace Tests\Feature\Actions;

use App\Actions\Bookings\GetAllBookings;
use App\Models\Booking;
use App\Models\Location;
use App\Models\Resource;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\CarbonImmutable;

class GetAllBookingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_all_bookings_filters_by_resources_and_date_range()
    {
        // Arrange: Create Resources
        $resources = Resource::factory()->count(3)->create();
        $unrelatedResource = Resource::factory()->create();

        // Arrange: Create Bookings
        $startDate = CarbonImmutable::parse('2024-01-01 08:00:00');
        $endDate = CarbonImmutable::parse('2024-01-01 18:00:00');

        $matchingBooking = Booking::factory()->create([
            'resource_id' => $resources[0]->id,
            'starts_at' => '2024-01-01 09:00:00',
            'ends_at' => '2024-01-01 17:00:00',
            'cancelled_at' => null,
        ]);

        $nonMatchingBooking = Booking::factory()->create([
            'resource_id' => $unrelatedResource->id,
            'starts_at' => '2024-01-01 10:00:00',
            'ends_at' => '2024-01-01 16:00:00',
            'cancelled_at' => null,
        ]);

        $cancelledBooking = Booking::factory()->create([
            'resource_id' => $resources[1]->id,
            'starts_at' => '2024-01-01 11:00:00',
            'ends_at' => '2024-01-01 15:00:00',
            'cancelled_at' => now(),
        ]);

        // Act: Call the GetAllBookings action
        $action = new GetAllBookings();
        $results = $action->get($resources, $startDate, $endDate);

        // Assert: Verify the correct bookings are returned
        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($matchingBooking));
        $this->assertFalse($results->contains($nonMatchingBooking));
        $this->assertFalse($results->contains($cancelledBooking));
    }

    public function test_get_all_bookings_includes_cancelled_when_flag_is_true()
    {
        // Arrange: Create Resources
        $resources = Resource::factory()->count(2)->create();

        // Arrange: Create Bookings
        $startDate = CarbonImmutable::parse('2024-01-01 08:00:00');
        $endDate = CarbonImmutable::parse('2024-01-01 18:00:00');

        $cancelledBooking = Booking::factory()->create([
            'resource_id' => $resources[0]->id,
            'starts_at' => '2024-01-01 11:00:00',
            'ends_at' => '2024-01-01 15:00:00',
            'cancelled_at' => now(),
        ]);

        // Act: Call the GetAllBookings action with includeCancelled = true
        $action = new GetAllBookings();
        $results = $action->get($resources, $startDate, $endDate, null, null, true);

        // Assert: Verify the cancelled booking is included
        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($cancelledBooking));
    }

    public function test_get_all_bookings_filters_by_location_and_service()
    {
        // Arrange: Create Resources
        $resources = Resource::factory()->count(2)->create();

        // Arrange: Create Bookings
        $startDate = CarbonImmutable::parse('2024-01-01 08:00:00');
        $endDate = CarbonImmutable::parse('2024-01-01 18:00:00');

        $service = Service::factory()->create();
        $location = Location::factory()->create();

        $matchingBooking = Booking::factory()->create([
            'resource_id' => $resources[0]->id,
            'starts_at' => '2024-01-01 09:00:00',
            'ends_at' => '2024-01-01 17:00:00',
            'location_id' => $location->id,
            'service_id' => $service->id,
            'cancelled_at' => null,
        ]);

        $nonMatchingBooking = Booking::factory()->create([
            'resource_id' => $resources[0]->id,
            'starts_at' => '2024-01-01 10:00:00',
            'ends_at' => '2024-01-01 16:00:00',
            'cancelled_at' => null,
        ]);

        // Act: Call the GetAllBookings action with location and service filters
        $action = new GetAllBookings();
        $results = $action->get($resources, $startDate, $endDate, $location->id, $service->id);

        // Assert: Verify only matching bookings are returned
        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($matchingBooking));
        $this->assertFalse($results->contains($nonMatchingBooking));
    }

    public function test_get_all_bookings_includes_partial_overlaps_with_time_range()
    {
        // Arrange: Create Resources
        $resources = Resource::factory()->count(1)->create();

        // Arrange: Define a 15-minute time range
        $startDate = CarbonImmutable::parse('2024-01-01 10:00:00');
        $endDate = CarbonImmutable::parse('2024-01-01 10:15:00');

        // Create a booking that overlaps the time range (1 hour long)
        $overlappingBooking = Booking::factory()->create([
            'resource_id' => $resources[0]->id,
            'starts_at' => '2024-01-01 09:30:00',
            'ends_at' => '2024-01-01 10:30:00',
            'cancelled_at' => null,
        ]);

        // Create a booking completely outside the time range
        $nonOverlappingBooking = Booking::factory()->create([
            'resource_id' => $resources[0]->id,
            'starts_at' => '2024-01-01 08:00:00',
            'ends_at' => '2024-01-01 09:00:00',
            'cancelled_at' => null,
        ]);

        // Act: Call the GetAllBookings action
        $action = new GetAllBookings();
        $results = $action->get($resources, $startDate, $endDate);

        // Assert: Verify that the partially overlapping booking is included
        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($overlappingBooking));
        $this->assertFalse($results->contains($nonOverlappingBooking));
    }

    public function test_get_all_bookings_includes_booking_with_exact_time_range()
    {
        // Arrange: Create Resources
        $resources = Resource::factory()->count(1)->create();

        // Arrange: Define an exact time range
        $startDate = CarbonImmutable::parse('2024-01-01 10:00:00');
        $endDate = CarbonImmutable::parse('2024-01-01 11:00:00');

        // Create a booking that matches the exact time range
        $exactBooking = Booking::factory()->create([
            'resource_id' => $resources[0]->id,
            'starts_at' => '2024-01-01 10:00:00',
            'ends_at' => '2024-01-01 11:00:00',
            'cancelled_at' => null,
        ]);

        // Create a booking outside the time range
        $nonMatchingBooking = Booking::factory()->create([
            'resource_id' => $resources[0]->id,
            'starts_at' => '2024-01-01 11:30:00',
            'ends_at' => '2024-01-01 12:30:00',
            'cancelled_at' => null,
        ]);

        // Act: Call the GetAllBookings action
        $action = new GetAllBookings();
        $results = $action->get($resources, $startDate, $endDate);

        // Assert: Verify that the exact match booking is included
        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($exactBooking));
        $this->assertFalse($results->contains($nonMatchingBooking));
    }

    public function test_get_all_bookings_includes_booking_that_fully_encloses_requested_range()
    {
        // Arrange
        $resources = Resource::factory()->count(1)->create();
        $startDate = CarbonImmutable::parse('2024-01-01 10:00:00');
        $endDate = CarbonImmutable::parse('2024-01-01 11:00:00');

        $enclosingBooking = Booking::factory()->create([
            'resource_id' => $resources[0]->id,
            'starts_at' => '2024-01-01 09:00:00',
            'ends_at' => '2024-01-01 12:00:00',
            'cancelled_at' => null,
        ]);

        $nonOverlappingBooking = Booking::factory()->create([
            'resource_id' => $resources[0]->id,
            'starts_at' => '2024-01-01 12:00:01',
            'ends_at' => '2024-01-01 13:00:00',
            'cancelled_at' => null,
        ]);

        // Act
        $action = new GetAllBookings();
        $results = $action->get($resources, $startDate, $endDate);

        // Assert
        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($enclosingBooking));
        $this->assertFalse($results->contains($nonOverlappingBooking));
    }

    public function test_get_all_bookings_includes_booking_fully_inside_requested_range()
    {
        // Arrange
        $resources = Resource::factory()->count(1)->create();
        $startDate = CarbonImmutable::parse('2024-01-01 09:00:00');
        $endDate = CarbonImmutable::parse('2024-01-01 12:00:00');

        $insideBooking = Booking::factory()->create([
            'resource_id' => $resources[0]->id,
            'starts_at' => '2024-01-01 10:00:00',
            'ends_at' => '2024-01-01 11:00:00',
            'cancelled_at' => null,
        ]);

        $nonOverlappingBooking = Booking::factory()->create([
            'resource_id' => $resources[0]->id,
            'starts_at' => '2024-01-01 12:00:01',
            'ends_at' => '2024-01-01 13:00:00',
            'cancelled_at' => null,
        ]);

        // Act
        $action = new GetAllBookings();
        $results = $action->get($resources, $startDate, $endDate);

        // Assert
        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($insideBooking));
        $this->assertFalse($results->contains($nonOverlappingBooking));
    }

    public function test_get_all_bookings_excludes_booking_that_ends_when_range_starts()
    {
        // Arrange
        $resources = Resource::factory()->count(1)->create();
        $startDate = CarbonImmutable::parse('2024-01-01 10:00:00');
        $endDate = CarbonImmutable::parse('2024-01-01 11:00:00');

        $nonOverlappingBooking = Booking::factory()->create([
            'resource_id' => $resources[0]->id,
            'starts_at' => '2024-01-01 09:00:00',
            'ends_at' => '2024-01-01 10:00:00',
            'cancelled_at' => null,
        ]);

        // Act
        $action = new GetAllBookings();
        $results = $action->get($resources, $startDate, $endDate);

        // Assert
        $this->assertCount(0, $results);
    }

    public function test_get_all_bookings_excludes_booking_that_starts_when_range_ends()
    {
        // Arrange
        $resources = Resource::factory()->count(1)->create();
        $startDate = CarbonImmutable::parse('2024-01-01 10:00:00');
        $endDate = CarbonImmutable::parse('2024-01-01 11:00:00');

        $nonOverlappingBooking = Booking::factory()->create([
            'resource_id' => $resources[0]->id,
            'starts_at' => '2024-01-01 11:00:00',
            'ends_at' => '2024-01-01 12:00:00',
            'cancelled_at' => null,
        ]);

        // Act
        $action = new GetAllBookings();
        $results = $action->get($resources, $startDate, $endDate);

        // Assert
        $this->assertCount(0, $results);
    }

    public function test_get_all_bookings_includes_booking_that_exactly_matches_requested_range()
    {
        // Arrange
        $resources = Resource::factory()->count(1)->create();
        $startDate = CarbonImmutable::parse('2024-01-01 10:00:00');
        $endDate = CarbonImmutable::parse('2024-01-01 11:00:00');

        $exactBooking = Booking::factory()->create([
            'resource_id' => $resources[0]->id,
            'starts_at' => '2024-01-01 10:00:00',
            'ends_at' => '2024-01-01 11:00:00',
            'cancelled_at' => null,
        ]);

        $nonMatchingBooking = Booking::factory()->create([
            'resource_id' => $resources[0]->id,
            'starts_at' => '2024-01-01 11:30:00',
            'ends_at' => '2024-01-01 12:30:00',
            'cancelled_at' => null,
        ]);

        // Act
        $action = new GetAllBookings();
        $results = $action->get($resources, $startDate, $endDate);

        // Assert
        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($exactBooking));
        $this->assertFalse($results->contains($nonMatchingBooking));
    }

    public function test_get_all_bookings_excludes_cancelled_bookings()
    {
        // Arrange
        $resources = Resource::factory()->count(1)->create();
        $startDate = CarbonImmutable::parse('2024-01-01 08:00:00');
        $endDate = CarbonImmutable::parse('2024-01-01 18:00:00');

        // Create a non-cancelled booking
        $activeBooking = Booking::factory()->create([
            'resource_id' => $resources[0]->id,
            'starts_at' => '2024-01-01 09:00:00',
            'ends_at' => '2024-01-01 11:00:00',
            'cancelled_at' => null,
        ]);

        // Create a cancelled booking
        $cancelledBooking = Booking::factory()->create([
            'resource_id' => $resources[0]->id,
            'starts_at' => '2024-01-01 12:00:00',
            'ends_at' => '2024-01-01 13:00:00',
            'cancelled_at' => now(),
        ]);

        // Act: Call the GetAllBookings action without including cancelled bookings
        $action = new GetAllBookings();
        $results = $action->get($resources, $startDate, $endDate);

        // Assert: Verify that only the active booking is included
        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($activeBooking));
        $this->assertFalse($results->contains($cancelledBooking));
    }
}
