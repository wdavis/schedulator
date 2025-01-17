<?php

namespace Tests\Feature\Actions;

use App\Actions\FormatSchedules;
use App\Actions\UpdateSchedule;
use App\Models\Location;
use App\Models\LocationResource;
use App\Models\Resource;
use App\Models\Schedule;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected UpdateSchedule $action;

    protected FormatSchedules $formatSchedules;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formatSchedules = $this->mock(FormatSchedules::class);
        $this->action = new UpdateSchedule($this->formatSchedules);
    }

    public function test_throws_exception_if_no_schedule_data_is_provided(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Schedule data is required');

        $resource = Resource::factory()->create();

        $this->action->execute($resource, []);
    }

    public function test_throws_exception_if_location_is_not_provided_and_resource_has_no_location(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No location provided and the resource does not have a primary location');

        $resource = Resource::factory()->create();

        $this->action->execute($resource, ['monday' => [['start_time' => '09:00', 'end_time' => '17:00']]]);
    }

    public function test_creates_and_formats_new_schedule_records(): void
    {
        // Arrange
        $resource = Resource::factory()->create();
        $location = Location::factory()->create();

        // Associate location and resource via LocationResource
        LocationResource::factory()->create([
            'location_id' => $location->id,
            'resource_id' => $resource->id,
        ]);

        $scheduleData = [
            'monday' => [
                ['start_time' => '09:00', 'end_time' => '12:00'],
                ['start_time' => '13:00', 'end_time' => '17:00'],
            ],
        ];

        $this->formatSchedules->shouldReceive('format')->andReturnUsing(function ($schedules) {
            return $schedules;
        });

        // Act
        $result = $this->action->execute($resource, $scheduleData, $location);

        // Assert
        $this->assertCount(2, Schedule::all());
        $this->assertCount(2, $result);

        $this->assertEquals('09:00:00', $result[0]->start_time);
        $this->assertEquals('12:00:00', $result[0]->end_time);
        $this->assertEquals(1, $result[0]->day_of_week);

        $this->assertEquals('13:00:00', $result[1]->start_time);
        $this->assertEquals('17:00:00', $result[1]->end_time);
        $this->assertEquals(1, $result[1]->day_of_week);
    }

    public function test_deletes_existing_schedules_and_replaces_with_new_ones(): void
    {
        // Arrange
        $resource = Resource::factory()->create();
        $location = Location::factory()->create();

        // Associate location and resource via LocationResource
        LocationResource::factory()->create([
            'location_id' => $location->id,
            'resource_id' => $resource->id,
        ]);

        Schedule::factory()->create([
            'resource_id' => $resource->id,
            'location_id' => $location->id,
            'day_of_week' => 1,
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
        ]);

        $scheduleData = [
            'monday' => [
                ['start_time' => '09:00', 'end_time' => '12:00'],
                ['start_time' => '13:00', 'end_time' => '17:00'],
            ],
        ];

        $this->formatSchedules->shouldReceive('format')->andReturnUsing(function ($schedules) {
            return $schedules;
        });

        // Act
        $result = $this->action->execute($resource, $scheduleData, $location);

        // Assert
        $this->assertCount(2, Schedule::all());
        $this->assertCount(2, $result);

        $this->assertDatabaseMissing('schedules', [
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
        ]);

        $this->assertDatabaseHas('schedules', [
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'day_of_week' => 1,
        ]);

        $this->assertDatabaseHas('schedules', [
            'start_time' => '13:00:00',
            'end_time' => '17:00:00',
            'day_of_week' => 1,
        ]);
    }

    public function test_throws_exception_if_invalid_day_is_provided_in_schedule_data(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid day provided: invalid_day');

        $resource = Resource::factory()->create();
        $location = Location::factory()->create();

        // Associate location and resource via LocationResource
        LocationResource::factory()->create([
            'location_id' => $location->id,
            'resource_id' => $resource->id,
        ]);

        $scheduleData = [
            'invalid_day' => [
                ['start_time' => '09:00', 'end_time' => '17:00'],
            ],
        ];

        $this->action->execute($resource, $scheduleData, $location);
    }

    public function test_throws_exception_if_start_or_end_time_is_missing_in_schedule(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Start time and end time are required for each schedule');

        $resource = Resource::factory()->create();
        $location = Location::factory()->create();

        // Associate location and resource via LocationResource
        LocationResource::factory()->create([
            'location_id' => $location->id,
            'resource_id' => $resource->id,
        ]);

        $scheduleData = [
            'monday' => [
                ['start_time' => '09:00'],
            ],
        ];

        $this->action->execute($resource, $scheduleData, $location);
    }
}
