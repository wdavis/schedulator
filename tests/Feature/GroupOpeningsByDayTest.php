<?php

namespace Tests\Feature;

use App\Actions\GroupOpeningsByDay;
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

        $result = $action->execute($slots, $timezone);

        $this->assertEquals($expected[0]['slots'][0], $result[0]['slots'][0]);
        $this->assertEquals($expected[0]['slots'][1], $result[0]['slots'][1]);

    }

    public function test_it_handles_multiple_slots_on_same_day()
    {
        $action = new GroupOpeningsByDay();
        $slots = [
            ['start' => '2024-05-29T23:00:00Z', 'end' => '2024-05-29T23:15:00Z'],
            ['start' => '2024-05-29T23:30:00Z', 'end' => '2024-05-29T23:45:00Z'],
        ];
        $timezone = 'America/New_York';

        $expected = [
            [
                'date' => '2024-05-29',
                'day' => 'Wed',
                'slots' => [
                    ['start' => '2024-05-29T19:00:00-04:00', 'end' => '2024-05-29T19:15:00-04:00'],
                    ['start' => '2024-05-29T19:30:00-04:00', 'end' => '2024-05-29T19:45:00-04:00'],
                ],
            ],
        ];

        $result = $action->execute($slots, $timezone);

        $this->assertEquals($expected, $result);
    }

    public function test_it_handles_empty_slots_array()
    {
        $action = new GroupOpeningsByDay();
        $slots = [];
        $timezone = 'America/New_York';

        $expected = [];

        $result = $action->execute($slots, $timezone);

        $this->assertEquals($expected, $result);
    }
}
