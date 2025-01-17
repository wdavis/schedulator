<?php

namespace Tests\Feature\Actions;

use App\Actions\GetScheduleByDay;
use App\Models\Booking;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class GetScheduleByDayTest extends TestCase
{
    protected GetScheduleByDay $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new GetScheduleByDay;
    }

    public function test_execute_with_single_opening_and_no_bookings()
    {
        // Arrange
        $openings = [
            ['start' => '2024-11-15T09:00:00Z', 'end' => '2024-11-15T12:00:00Z'],
        ];
        $bookings = collect([]);
        $startDate = CarbonImmutable::parse('2024-11-15');
        $endDate = CarbonImmutable::parse('2024-11-15');
        $timezone = 'UTC';

        // Act
        $result = $this->action->execute($openings, $bookings, $startDate, $endDate, $timezone);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals('2024-11-15', $result[0]['date']);
        $this->assertEquals('Fri', $result[0]['day']);
        $this->assertCount(1, $result[0]['slots']);
        $this->assertEquals('2024-11-15T09:00:00+00:00', $result[0]['slots'][0]['start']);
        $this->assertEquals('2024-11-15T12:00:00+00:00', $result[0]['slots'][0]['end']);
    }

    public function test_execute_with_multiple_openings_and_bookings()
    {
        // Arrange
        $openings = [
            ['start' => '2024-11-15T09:00:00Z', 'end' => '2024-11-15T12:00:00Z'],
            ['start' => '2024-11-15T13:00:00Z', 'end' => '2024-11-15T15:00:00Z'],
        ];
        $bookings = collect([
            Booking::factory()->make(['starts_at' => '2024-11-15T10:00:00Z', 'ends_at' => '2024-11-15T11:00:00Z']),
        ]);
        $startDate = CarbonImmutable::parse('2024-11-15');
        $endDate = CarbonImmutable::parse('2024-11-15');
        $timezone = 'UTC';

        // Act
        $result = $this->action->execute($openings, $bookings, $startDate, $endDate, $timezone);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals('2024-11-15', $result[0]['date']);
        $this->assertEquals('Fri', $result[0]['day']);
        $this->assertCount(4, $result[0]['slots']);

        // Check opening slot before booking
        $this->assertEquals('2024-11-15T09:00:00+00:00', $result[0]['slots'][0]['start']);
        $this->assertEquals('2024-11-15T12:00:00+00:00', $result[0]['slots'][0]['end']);
        $this->assertEquals('opening_slot', $result[0]['slots'][0]['type']);

        $this->assertCount(1, $result[0]['slots'][0]['openings']);
        $this->assertCount(0, $result[0]['slots'][0]['bookings']);

        $this->assertEquals('2024-11-15T09:00:00+00:00', $result[0]['slots'][0]['openings'][0]['start']);
        $this->assertEquals('2024-11-15T12:00:00+00:00', $result[0]['slots'][0]['openings'][0]['end']);
        $this->assertEquals('opening', $result[0]['slots'][0]['openings'][0]['type']);

        // Check booking slot
        $this->assertEquals('booking_slot', $result[0]['slots'][1]['type']);
        $this->assertEquals('2024-11-15T10:00:00+00:00', $result[0]['slots'][1]['start']);
        $this->assertEquals('2024-11-15T11:00:00+00:00', $result[0]['slots'][1]['end']);

        $this->assertCount(0, $result[0]['slots'][1]['openings']);
        $this->assertCount(1, $result[0]['slots'][1]['bookings']);

        $this->assertEquals('booking', $result[0]['slots'][1]['bookings'][0]['type']);
        $this->assertEquals('2024-11-15T10:00:00+00:00', $result[0]['slots'][1]['bookings'][0]['start']->toIso8601String());
        $this->assertEquals('2024-11-15T11:00:00+00:00', $result[0]['slots'][1]['bookings'][0]['end']->toIso8601String());

        // Check opening slot after booking
        $this->assertEquals('opening_slot', $result[0]['slots'][3]['type']);
        $this->assertEquals('2024-11-15T13:00:00+00:00', $result[0]['slots'][3]['start']);
        $this->assertEquals('2024-11-15T15:00:00+00:00', $result[0]['slots'][3]['end']);
    }

    public function test_execute_with_multiple_bookings_in_same_slot()
    {
        // Arrange
        $openings = [];
        $bookings = collect([
            Booking::factory()->make(['starts_at' => '2024-11-15T10:00:00Z', 'ends_at' => '2024-11-15T10:15:00Z']),
            Booking::factory()->make(['starts_at' => '2024-11-15T10:00:00Z', 'ends_at' => '2024-11-15T10:15:00Z']),
        ]);
        $startDate = CarbonImmutable::parse('2024-11-15');
        $endDate = CarbonImmutable::parse('2024-11-15');
        $timezone = 'UTC';

        // Act
        $result = $this->action->execute($openings, $bookings, $startDate, $endDate, $timezone);

        // Check first booking slot
        $this->assertEquals('booking_slot', $result[0]['slots'][0]['type']);
        $this->assertEquals('2024-11-15T10:00:00+00:00', $result[0]['slots'][0]['start']);
        $this->assertEquals('2024-11-15T10:15:00+00:00', $result[0]['slots'][0]['end']);

        $this->assertCount(0, $result[0]['slots'][0]['openings']);
        $this->assertCount(2, $result[0]['slots'][0]['bookings']);

        $this->assertEquals('booking', $result[0]['slots'][0]['bookings'][0]['type']);
        $this->assertEquals('2024-11-15T10:00:00+00:00', $result[0]['slots'][0]['bookings'][0]['start']->toIso8601String());
        $this->assertEquals('2024-11-15T10:15:00+00:00', $result[0]['slots'][0]['bookings'][0]['end']->toIso8601String());
        $this->assertEquals($bookings[0]['id'], $result[0]['slots'][0]['bookings'][0]['record']['id']);
        $this->assertEquals($bookings[0]['name'], $result[0]['slots'][0]['bookings'][0]['record']['name']);
        $this->assertEquals($bookings[0]['service_id'], $result[0]['slots'][0]['bookings'][0]['record']['service_id']);
        $this->assertEquals($bookings[0]['resource_id'], $result[0]['slots'][0]['bookings'][0]['record']['resource_id']);
        $this->assertEquals($bookings[0]['meta'], $result[0]['slots'][0]['bookings'][0]['record']['meta']);
        $this->assertEquals($bookings[0]['created_at'], $result[0]['slots'][0]['bookings'][0]['record']['created_at']);
        $this->assertEquals($bookings[0]['updated_at'], $result[0]['slots'][0]['bookings'][0]['record']['updated_at']);

        // Check second booking slot
        $this->assertEquals('booking', $result[0]['slots'][0]['bookings'][1]['type']);
        $this->assertEquals('2024-11-15T10:00:00+00:00', $result[0]['slots'][0]['bookings'][1]['start']->toIso8601String());
        $this->assertEquals('2024-11-15T10:15:00+00:00', $result[0]['slots'][0]['bookings'][1]['end']->toIso8601String());
        $this->assertEquals($bookings[1]['id'], $result[0]['slots'][0]['bookings'][1]['record']['id']);
        $this->assertEquals($bookings[1]['name'], $result[0]['slots'][0]['bookings'][1]['record']['name']);
        $this->assertEquals($bookings[1]['service_id'], $result[0]['slots'][0]['bookings'][1]['record']['service_id']);
        $this->assertEquals($bookings[1]['resource_id'], $result[0]['slots'][0]['bookings'][1]['record']['resource_id']);
        $this->assertEquals($bookings[1]['meta'], $result[0]['slots'][0]['bookings'][1]['record']['meta']);
        $this->assertEquals($bookings[1]['created_at'], $result[0]['slots'][0]['bookings'][1]['record']['created_at']);
        $this->assertEquals($bookings[1]['updated_at'], $result[0]['slots'][0]['bookings'][1]['record']['updated_at']);
    }

    public function test_execute_with_gap_between_slots()
    {
        // Arrange
        $openings = [
            ['start' => '2024-11-15T09:00:00Z', 'end' => '2024-11-15T10:00:00Z'],
            ['start' => '2024-11-15T11:00:00Z', 'end' => '2024-11-15T12:00:00Z'],
        ];
        $bookings = collect([]);
        $startDate = CarbonImmutable::parse('2024-11-15');
        $endDate = CarbonImmutable::parse('2024-11-15');
        $timezone = 'UTC';

        // Act
        $result = $this->action->execute($openings, $bookings, $startDate, $endDate, $timezone);

        // Assert
        $this->assertCount(1, $result);
        $this->assertCount(3, $result[0]['slots']);

        // Check first opening
        $this->assertEquals('2024-11-15T09:00:00+00:00', $result[0]['slots'][0]['start']);
        $this->assertEquals('2024-11-15T10:00:00+00:00', $result[0]['slots'][0]['end']);

        // Check gap
        $this->assertEquals('gap', $result[0]['slots'][1]['type']);
        $this->assertEquals('2024-11-15T10:00:00+00:00', $result[0]['slots'][1]['start']);
        $this->assertEquals('2024-11-15T11:00:00+00:00', $result[0]['slots'][1]['end']);

        // Check second opening
        $this->assertEquals('2024-11-15T11:00:00+00:00', $result[0]['slots'][2]['start']);
        $this->assertEquals('2024-11-15T12:00:00+00:00', $result[0]['slots'][2]['end']);
    }

    public function test_execute_with_empty_openings_and_bookings()
    {
        // Arrange
        $openings = [];
        $bookings = collect([]);
        $startDate = CarbonImmutable::parse('2024-11-15');
        $endDate = CarbonImmutable::parse('2024-11-15');
        $timezone = 'UTC';

        // Act
        $result = $this->action->execute($openings, $bookings, $startDate, $endDate, $timezone);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals('2024-11-15', $result[0]['date']);
        $this->assertCount(0, $result[0]['slots']);
    }
}
