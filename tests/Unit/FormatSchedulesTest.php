<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Actions\FormatSchedules;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class FormatSchedulesTest extends TestCase
{
    protected $formatSchedules;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formatSchedules = new FormatSchedules();
    }

    public function test_formats_all_days_correctly_when_no_days_missing()
    {
        $schedules = new Collection([
            $this->createSchedule(1, '09:00', '17:00'), // Monday
            $this->createSchedule(2, '09:00', '17:00'), // Tuesday
            $this->createSchedule(3, '09:00', '17:00'), // Wednesday
            $this->createSchedule(4, '09:00', '17:00'), // Thursday
            $this->createSchedule(5, '09:00', '17:00'), // Friday
            $this->createSchedule(6, '09:00', '17:00'), // Saturday
            $this->createSchedule(7, '09:00', '17:00'), // Sunday
        ]);

        $formatted = $this->formatSchedules->format($schedules);

        $this->assertCount(7, $formatted);

        $this->assertArrayHasKey('monday', $formatted);
        $this->assertArrayHasKey('tuesday', $formatted);
        $this->assertArrayHasKey('wednesday', $formatted);
        $this->assertArrayHasKey('thursday', $formatted);
        $this->assertArrayHasKey('friday', $formatted);
        $this->assertArrayHasKey('saturday', $formatted);
        $this->assertArrayHasKey('sunday', $formatted);

        $this->assertSame('09:00:00', $formatted['monday'][0]['start_time']);
        $this->assertSame('17:00:00', $formatted['monday'][0]['end_time']);
    }

    public function test_formats_days_with_missing_days()
    {
        $schedules = new Collection([
            $this->createSchedule(1, '09:00', '17:00'), // Monday
            $this->createSchedule(5, '09:00', '17:00'), // Friday
        ]);

        $formatted = $this->formatSchedules->format($schedules);

        $this->assertCount(7, $formatted);

        $this->assertSame([], $formatted['tuesday']);
        $this->assertSame([], $formatted['wednesday']);
        $this->assertSame([], $formatted['thursday']);
        $this->assertSame([], $formatted['saturday']);
        $this->assertSame([], $formatted['sunday']);

        $this->assertSame('09:00:00', $formatted['monday'][0]['start_time']);
        $this->assertSame('17:00:00', $formatted['monday'][0]['end_time']);
        $this->assertSame('09:00:00', $formatted['friday'][0]['start_time']);
        $this->assertSame('17:00:00', $formatted['friday'][0]['end_time']);
    }

    public function test_formats_empty_schedules()
    {
        $schedules = new Collection([]);

        $formatted = $this->formatSchedules->format($schedules);

        $this->assertCount(7, $formatted);

        $this->assertSame([], $formatted['monday']);
        $this->assertSame([], $formatted['tuesday']);
        $this->assertSame([], $formatted['wednesday']);
        $this->assertSame([], $formatted['thursday']);
        $this->assertSame([], $formatted['friday']);
        $this->assertSame([], $formatted['saturday']);
        $this->assertSame([], $formatted['sunday']);
    }

    private function createSchedule(int $dayOfWeek, string $startTime, string $endTime)
    {
        return (object) [
            'day_of_week' => $dayOfWeek,
            'start_time' => Carbon::parse($startTime),
            'end_time' => Carbon::parse($endTime),
        ];
    }
}
