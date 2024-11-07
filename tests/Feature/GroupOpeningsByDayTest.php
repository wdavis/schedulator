<?php

namespace Tests\Feature;

use App\Actions\GroupOpeningsByDay;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class GroupOpeningsByDayTest extends TestCase
{
    public function test_it_groups_slots_by_day()
    {
        $action = new GroupOpeningsByDay();
        $slots = [
            ['start' => '2024-05-29T23:00:00Z', 'end' => '2024-05-29T23:15:00Z'],
            ['start' => '2024-05-30T00:00:00Z', 'end' => '2024-05-30T00:15:00Z'],
        ];
        $timezone = 'America/New_York';

        $expected = [
            [
                'date' => '2024-05-29',
                'day' => 'Wed',
                'slots' => [
                    ['start' => '2024-05-29T19:00:00-04:00', 'end' => '2024-05-29T19:15:00-04:00'],
                    ['start' => '2024-05-29T20:00:00-04:00', 'end' => '2024-05-29T20:15:00-04:00'],
                ],
            ],
        ];

        $startDate = CarbonImmutable::parse('2024-05-29T05:00:00Z');
        $endDate = CarbonImmutable::parse('2024-05-30T05:00:00Z');

        $result = $action->execute($slots, $startDate, $endDate, $timezone);

        $this->assertEquals($expected[0]['day'], $result[0]['day']);
        $this->assertEquals($expected[0]['slots'][0], $result[0]['slots'][0]);
        $this->assertEquals($expected[0]['slots'][1], $result[0]['slots'][1]);
    }

    public function test_it_handles_multiple_slots_on_same_day()
    {
        $action = new GroupOpeningsByDay();
        $slots = [
            ['start' => '2024-11-03T10:00:00-06:00', 'end' => '2024-11-03T10:15:00-06:00'],
            ['start' => '2024-11-03T10:15:00-06:00', 'end' => '2024-11-03T10:30:00-06:00'],
        ];
        $timezone = 'America/Chicago';

        $expected = [];

        $startDate = CarbonImmutable::parse('2024-11-03T00:00:00-05:00'); // first day of time change
        $endDate = CarbonImmutable::parse('2024-11-09T23:59:59-06:00');

        $result = $action->execute($slots, $startDate, $endDate, $timezone);

        $this->assertEquals('Sun', $result[0]['day']);
        $this->assertCount(2, $result[0]['slots']);
        $this->assertEquals('2024-11-03T10:00:00-06:00', $result[0]['slots'][0]['start']);
        $this->assertEquals('2024-11-03T10:15:00-06:00', $result[0]['slots'][0]['end']);
        $this->assertEquals('2024-11-03T10:15:00-06:00', $result[0]['slots'][1]['start']);
        $this->assertEquals('2024-11-03T10:30:00-06:00', $result[0]['slots'][1]['end']);

    }

    public function test_it_handles_duplicate_day_scenario()
    {
        $action = new GroupOpeningsByDay();
        $slots = [];
        $timezone = 'America/Chicago';

        $expected = [];

        $startDate = CarbonImmutable::parse('2024-11-03T00:00:00-05:00'); // first day of time change
        $endDate = CarbonImmutable::parse('2024-11-09T23:59:59-06:00');

        $result = $action->execute($slots, $startDate, $endDate, $timezone);

        $this->assertCount(7, $result);
        $this->assertEquals('Sun', $result[0]['day']);
        $this->assertEquals('Mon', $result[1]['day']);
        $this->assertEquals('Tue', $result[2]['day']);
        $this->assertEquals('Wed', $result[3]['day']);
        $this->assertEquals('Thu', $result[4]['day']);
        $this->assertEquals('Fri', $result[5]['day']);
        $this->assertEquals('Sat', $result[6]['day']);

    }

    public function test_it_handles_dst_start_spring_forward()
    {
        $action = new GroupOpeningsByDay();
        $slots = [
            ['start' => '2024-03-10T01:00:00-05:00', 'end' => '2024-03-10T03:00:00-04:00'],
        ];
        $timezone = 'America/New_York';

        $startDate = CarbonImmutable::parse('2024-03-09T00:00:00-05:00');
        $endDate = CarbonImmutable::parse('2024-03-11T23:59:59-04:00');

        $result = $action->execute($slots, $startDate, $endDate, $timezone);

        $this->assertCount(3, $result);
        $this->assertEquals('Sun', $result[1]['day']);
        $this->assertEquals('2024-03-10T01:00:00-05:00', $result[1]['slots'][0]['start']);
        $this->assertEquals('2024-03-10T03:00:00-04:00', $result[1]['slots'][0]['end']);
    }

    public function test_it_handles_timezone_without_dst()
    {
        $action = new GroupOpeningsByDay();
        $slots = [
            ['start' => '2024-05-01T08:00:00-07:00', 'end' => '2024-05-01T10:00:00-07:00'],
        ];
        $timezone = 'America/Phoenix'; // No DST

        $startDate = CarbonImmutable::parse('2024-04-30T00:00:00-07:00');
        $endDate = CarbonImmutable::parse('2024-05-02T23:59:59-07:00');

        $result = $action->execute($slots, $startDate, $endDate, $timezone);

        $this->assertCount(3, $result);
        $this->assertEquals('Wed', $result[1]['day']);
        $this->assertCount(1, $result[1]['slots']);
        $this->assertEquals('2024-05-01T08:00:00-07:00', $result[1]['slots'][0]['start']);
        $this->assertEquals('2024-05-01T10:00:00-07:00', $result[1]['slots'][0]['end']);
    }

    public function test_it_handles_leap_year_date()
    {
        $action = new GroupOpeningsByDay();
        $slots = [
            ['start' => '2024-02-29T04:00:00-06:00', 'end' => '2024-02-29T05:00:00-06:00'], // Converted to America/Chicago time
        ];
        $timezone = 'America/Chicago';

        $startDate = CarbonImmutable::parse('2024-02-28T00:00:00-06:00'); // Local time
        $endDate = CarbonImmutable::parse('2024-03-01T23:59:59-06:00'); // Local time

        $result = $action->execute($slots, $startDate, $endDate, $timezone);

        $this->assertCount(3, $result);
        $this->assertEquals('Thu', $result[1]['day']);
        $this->assertCount(1, $result[1]['slots']);
        $this->assertEquals('2024-02-29T04:00:00-06:00', $result[1]['slots'][0]['start']);
        $this->assertEquals('2024-02-29T05:00:00-06:00', $result[1]['slots'][0]['end']);
    }

    public function test_it_handles_empty_slot_array()
    {
        $action = new GroupOpeningsByDay();
        $slots = [];
        $timezone = 'America/New_York';

        $startDate = CarbonImmutable::parse('2024-05-01T00:00:00-04:00');
        $endDate = CarbonImmutable::parse('2024-05-07T23:59:59-04:00');

        $result = $action->execute($slots, $startDate, $endDate, $timezone);

        $this->assertCount(7, $result);
        $this->assertEmpty($result[0]['slots']);
    }
}
