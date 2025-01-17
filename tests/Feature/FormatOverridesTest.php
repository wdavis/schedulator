<?php

namespace Tests\Feature;

use App\Actions\FormatOverrides;
use App\Models\ScheduleOverride;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class FormatOverridesTest extends TestCase
{
    /**
     * Test formatting with timezone conversion.
     *
     * @return void
     */
    public function test_format_with_timezone_conversion(): void
    {
        $formatOverrides = new FormatOverrides;

        // Create a mock collection of ScheduleOverride entries
        // dates are stored in UTC, but we want to format them in America/Chicago timezone
        $scheduleOverrides = collect([
            $this->createScheduleOverride('2024-09-02 13:00:00', '2024-09-02 14:00:00', 'opening'),
            $this->createScheduleOverride('2024-09-04 19:00:00', '2024-09-04 20:30:00', 'block'),
        ]);

        $timezone = 'America/Chicago'; // Testing with a different timezone
        $startDate = CarbonImmutable::parse('2024-09-01', $timezone)->startOfDay();
        $endDate = CarbonImmutable::parse('2024-09-05', $timezone)->endOfDay();

        $formatted = $formatOverrides->format($scheduleOverrides, $startDate, $endDate, $timezone);

        // Check the total number of days (should be 5 days)
        $this->assertCount(5, $formatted);

        // Check specific dates
        $day2 = $formatted->firstWhere('date', '2024-09-02');
        $this->assertCount(1, $day2['schedule']);
        $this->assertEquals('opening', $day2['schedule'][0]['type']);
        $this->assertEquals('2024-09-02T08:00:00-05:00', $day2['schedule'][0]['starts_at']);
        $this->assertEquals('2024-09-02T09:00:00-05:00', $day2['schedule'][0]['ends_at']);

        $day4 = $formatted->firstWhere('date', '2024-09-04');
        $this->assertCount(1, $day4['schedule']);
        $this->assertEquals('block', $day4['schedule'][0]['type']);
        $this->assertEquals('2024-09-04T14:00:00-05:00', $day4['schedule'][0]['starts_at']);
        $this->assertEquals('2024-09-04T15:30:00-05:00', $day4['schedule'][0]['ends_at']);
    }

    public function test_handles_timezone_change_days_without_repeating(): void
    {
        $formatOverrides = new FormatOverrides;

        // Create a mock collection of ScheduleOverride entries
        // dates are stored in UTC, but we want to format them in America/Chicago timezone
        $scheduleOverrides = collect([
            $this->createScheduleOverride('2024-11-03T01:00:00-05:00', '2024-11-03T02:00:00-05:00', 'opening'),
            $this->createScheduleOverride('2024-11-03T13:00:00-06:00', '2024-11-03T14:00:00-06:00', 'opening'),
            $this->createScheduleOverride('2024-11-03T13:30:00-06:00', '2024-11-03T14:30:00-06:00', 'block'),
        ]);

        $timezone = 'America/Chicago'; // Testing with a different timezone
        $startDate = CarbonImmutable::parse('2024-11-01', $timezone)->startOfDay();
        $endDate = CarbonImmutable::parse('2024-11-30', $timezone)->endOfDay();

        $formatted = $formatOverrides->format($scheduleOverrides, $startDate, $endDate, $timezone);

        $this->assertCount(30, $formatted);
        // Check specific dates
        $potentiallyRepeated = $formatted->where('date', '2024-11-03');
        $this->assertCount(1, $potentiallyRepeated);
    }

    /**
     * Helper function to create a mock ScheduleOverride instance.
     *
     * @param  string  $startsAt
     * @param  string  $endsAt
     * @param  string  $type
     * @return ScheduleOverride
     */
    private function createScheduleOverride($startsAt, $endsAt, $type)
    {
        return ScheduleOverride::factory()->make([
            'starts_at' => CarbonImmutable::parse($startsAt, 'UTC')->toIso8601ZuluString(),
            'ends_at' => CarbonImmutable::parse($endsAt, 'UTC')->toIso8601ZuluString(),
            'type' => $type,
        ]);
    }
}
