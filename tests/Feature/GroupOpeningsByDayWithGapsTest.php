<?php

namespace Tests\Feature;

use App\Actions\GroupOpeningsByDayWithGaps;
use Tests\TestCase;

class GroupOpeningsByDayWithGapsTest extends TestCase
{
    public function test_it_groups_openings_and_gaps_by_day()
    {
        $action = new GroupOpeningsByDayWithGaps;
        $openings = [
            ['start' => '2024-05-29T23:00:00.000000Z', 'end' => '2024-05-29T23:15:00.000000Z'],
            ['start' => '2024-05-29T23:30:00.000000Z', 'end' => '2024-05-29T23:45:00.000000Z'],
            ['start' => '2024-05-30T00:00:00.000000Z', 'end' => '2024-05-30T00:15:00.000000Z'],
        ];

        $expected = [
            [
                'date' => '2024-05-29',
                'day' => 'Wed',
                'slots' => [
                    [
                        'type' => 'opening',
                        'start' => '2024-05-29T23:00:00.000000Z',
                        'end' => '2024-05-29T23:15:00.000000Z',
                    ],
                    [
                        'type' => 'gap',
                        'start' => '2024-05-29T23:15:00.000000Z',
                        'end' => '2024-05-29T23:30:00.000000Z',
                    ],
                    [
                        'type' => 'opening',
                        'start' => '2024-05-29T23:30:00.000000Z',
                        'end' => '2024-05-29T23:45:00.000000Z',
                    ],
                    [
                        'type' => 'gap',
                        'start' => '2024-05-29T23:45:00.000000Z',
                        'end' => '2024-05-30T00:00:00.000000Z',
                    ],
                ],
            ],
            [
                'date' => '2024-05-30',
                'day' => 'Thu',
                'slots' => [
                    [
                        'type' => 'opening',
                        'start' => '2024-05-30T00:00:00.000000Z',
                        'end' => '2024-05-30T00:15:00.000000Z',
                    ],
                ],
            ],
        ];

        $result = $action->execute($openings);

        $this->assertEquals($expected, $result);
    }

    public function test_it_handles_multiple_openings_with_no_gaps()
    {
        $action = new GroupOpeningsByDayWithGaps;
        $openings = [
            ['start' => '2024-05-29T23:00:00.000000Z', 'end' => '2024-05-29T23:15:00.000000Z'],
            ['start' => '2024-05-29T23:15:00.000000Z', 'end' => '2024-05-29T23:30:00.000000Z'],
        ];

        $expected = [
            [
                'date' => '2024-05-29',
                'day' => 'Wed',
                'slots' => [
                    [
                        'type' => 'opening',
                        'start' => '2024-05-29T23:00:00.000000Z',
                        'end' => '2024-05-29T23:15:00.000000Z',
                    ],
                    [
                        'type' => 'opening',
                        'start' => '2024-05-29T23:15:00.000000Z',
                        'end' => '2024-05-29T23:30:00.000000Z',
                    ],
                ],
            ],
        ];

        $result = $action->execute($openings);

        $this->assertEquals($expected, $result);
    }

    public function test_it_handles_empty_openings_array()
    {
        $action = new GroupOpeningsByDayWithGaps;
        $openings = [];

        $expected = [];

        $result = $action->execute($openings);

        $this->assertEquals($expected, $result);
    }

    public function test_it_handles_large_gap_between_openings()
    {
        $action = new GroupOpeningsByDayWithGaps;
        $openings = [
            ['start' => '2024-05-29T10:00:00.000000Z', 'end' => '2024-05-29T10:15:00.000000Z'],
            ['start' => '2024-05-29T18:00:00.000000Z', 'end' => '2024-05-29T18:15:00.000000Z'],
        ];

        $expected = [
            [
                'date' => '2024-05-29',
                'day' => 'Wed',
                'slots' => [
                    [
                        'type' => 'opening',
                        'start' => '2024-05-29T10:00:00.000000Z',
                        'end' => '2024-05-29T10:15:00.000000Z',
                    ],
                    [
                        'type' => 'gap',
                        'start' => '2024-05-29T10:15:00.000000Z',
                        'end' => '2024-05-29T18:00:00.000000Z',
                    ],
                    [
                        'type' => 'opening',
                        'start' => '2024-05-29T18:00:00.000000Z',
                        'end' => '2024-05-29T18:15:00.000000Z',
                    ],
                ],
            ],
        ];

        $result = $action->execute($openings);

        $this->assertEquals($expected, $result);
    }

    public function test_it_equalizes_all_days()
    {
        $action = new GroupOpeningsByDayWithGaps;
        $openings = [
            ['start' => '2024-05-29T10:00:00.000000Z', 'end' => '2024-05-29T10:15:00.000000Z'],
            ['start' => '2024-05-29T18:00:00.000000Z', 'end' => '2024-05-29T18:15:00.000000Z'],
            ['start' => '2024-05-30T12:00:00.000000Z', 'end' => '2024-05-30T12:15:00.000000Z'],
        ];

        $expected = [
            [
                'date' => '2024-05-29',
                'day' => 'Wed',
                'slots' => [
                    [
                        'type' => 'gap',
                        'start' => '2024-05-29T00:00:00.000000Z',
                        'end' => '2024-05-29T10:00:00.000000Z',
                    ],
                    [
                        'type' => 'opening',
                        'start' => '2024-05-29T10:00:00.000000Z',
                        'end' => '2024-05-29T10:15:00.000000Z',
                    ],
                    [
                        'type' => 'gap',
                        'start' => '2024-05-29T10:15:00.000000Z',
                        'end' => '2024-05-29T18:00:00.000000Z',
                    ],
                    [
                        'type' => 'opening',
                        'start' => '2024-05-29T18:00:00.000000Z',
                        'end' => '2024-05-29T18:15:00.000000Z',
                    ],
                    [
                        'type' => 'gap',
                        'start' => '2024-05-29T18:15:00.000000Z',
                        'end' => '2024-05-29T23:59:59.999999Z',
                    ],
                ],
            ],
            [
                'date' => '2024-05-30',
                'day' => 'Thu',
                'slots' => [
                    [
                        'type' => 'gap',
                        'start' => '2024-05-30T00:00:00.000000Z',
                        'end' => '2024-05-30T12:00:00.000000Z',
                    ],
                    [
                        'type' => 'opening',
                        'start' => '2024-05-30T12:00:00.000000Z',
                        'end' => '2024-05-30T12:15:00.000000Z',
                    ],
                    [
                        'type' => 'gap',
                        'start' => '2024-05-30T12:15:00.000000Z',
                        'end' => '2024-05-30T23:59:59.999999Z',
                    ],
                ],
            ],
        ];

        $result = $action->execute($openings);

        $this->assertEquals($expected, $result);
    }
}
