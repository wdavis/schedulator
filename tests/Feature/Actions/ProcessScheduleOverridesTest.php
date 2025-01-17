<?php

namespace Tests\Feature\Actions;

use App\Actions\ProcessScheduleOverrides;
use App\Models\Location;
use App\Models\Resource;
use App\Models\ScheduleOverride;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProcessScheduleOverridesTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_adds_multiple_overrides_per_day(): void
    {
        $location = Location::factory()->create();
        $resource = Resource::factory()->create();

        $overrides = [
            [
                'id' => null, // New record
                'starts_at' => Carbon::parse('2024-10-15 09:00:00'),
                'ends_at' => Carbon::parse('2024-10-15 12:00:00'),
                'type' => 'opening',
            ],
            [
                'id' => null, // New record
                'starts_at' => Carbon::parse('2024-10-15 13:00:00'),
                'ends_at' => Carbon::parse('2024-10-15 16:00:00'),
                'type' => 'block',
            ],
        ];

        $action = new ProcessScheduleOverrides;
        $updatedRecords = $action->execute(
            overrides: $overrides,
            resourceId: $resource->id,
            locationId: $location->id,
            month: '2024-10',
            timezone: 'UTC'
        );

        $this->assertCount(2, $updatedRecords);
        $this->assertEquals('opening', $updatedRecords[0]->type);
        $this->assertEquals('block', $updatedRecords[1]->type);
    }

    /** @test */
    public function it_updates_existing_overrides(): void
    {
        $location = Location::factory()->create();
        $resource = Resource::factory()->create();
        $existingOverride = ScheduleOverride::factory()->create([
            'resource_id' => $resource->id,
            'location_id' => $location->id,
            'starts_at' => Carbon::parse('2024-10-10 09:00:00'),
            'ends_at' => Carbon::parse('2024-10-10 12:00:00'),
            'type' => 'opening',
        ]);

        $overrides = [
            [
                'id' => $existingOverride->id, // Update record
                'starts_at' => Carbon::parse('2024-10-10 10:00:00'),
                'ends_at' => Carbon::parse('2024-10-10 13:00:00'),
                'type' => 'block',
            ],
        ];

        $action = new ProcessScheduleOverrides;
        $updatedRecords = $action->execute(
            overrides: $overrides,
            resourceId: $resource->id,
            locationId: $location->id,
            month: '2024-10',
            timezone: 'UTC'
        );

        $this->assertCount(1, $updatedRecords);
        $this->assertEquals('block', $updatedRecords[0]->type);
        $this->assertEquals(Carbon::parse('2024-10-10 10:00:00'), $updatedRecords[0]->starts_at);
    }

    /** @test */
    public function it_deletes_removed_overrides(): void
    {
        $location = Location::factory()->create();
        $resource = Resource::factory()->create();
        $existingOverride = ScheduleOverride::factory()->create([
            'resource_id' => $resource->id,
            'location_id' => $location->id,
            'starts_at' => Carbon::parse('2024-10-12 09:00:00'),
            'ends_at' => Carbon::parse('2024-10-12 12:00:00'),
            'type' => 'opening',
        ]);

        $overrides = [
            [
                'id' => null, // New record, old one will be deleted
                'starts_at' => Carbon::parse('2024-10-15 09:00:00'),
                'ends_at' => Carbon::parse('2024-10-15 12:00:00'),
                'type' => 'opening',
            ],
        ];

        $action = new ProcessScheduleOverrides;
        $updatedRecords = $action->execute(
            overrides: $overrides,
            resourceId: $resource->id,
            locationId: $location->id,
            month: '2024-10',
            timezone: 'UTC'
        );

        $this->assertCount(1, $updatedRecords);
        $this->assertDatabaseMissing('schedule_overrides', ['id' => $existingOverride->id]);
    }

    /** @test */
    public function it_creates_new_overrides(): void
    {
        $location = Location::factory()->create();
        $resource = Resource::factory()->create();

        $overrides = [
            [
                'id' => null, // New record
                'starts_at' => Carbon::parse('2024-10-15 09:00:00'),
                'ends_at' => Carbon::parse('2024-10-15 12:00:00'),
                'type' => 'opening',
            ],
        ];

        $action = new ProcessScheduleOverrides;
        $updatedRecords = $action->execute(
            overrides: $overrides,
            resourceId: $resource->id,
            locationId: $location->id,
            month: '2024-10',
            timezone: 'UTC'
        );

        $this->assertCount(1, $updatedRecords);
        $this->assertDatabaseHas('schedule_overrides', [
            'resource_id' => $resource->id,
            'location_id' => $location->id,
            'starts_at' => Carbon::parse('2024-10-15 09:00:00'),
            'ends_at' => Carbon::parse('2024-10-15 12:00:00'),
            'type' => 'opening',
        ]);
    }

    /** @test */
    public function it_updates_a_mix_of_new_and_existing_records(): void
    {
        // Create location, resource, and an existing override using factories
        $location = Location::factory()->create();
        $resource = Resource::factory()->create();
        $existingOverride = ScheduleOverride::factory()->create([
            'resource_id' => $resource->id,
            'location_id' => $location->id,
            'starts_at' => Carbon::parse('2024-10-12 09:00:00'),
            'ends_at' => Carbon::parse('2024-10-12 12:00:00'),
            'type' => 'opening',
        ]);

        $overrides = [
            [
                'id' => $existingOverride->id, // Update existing record
                'starts_at' => Carbon::parse('2024-10-12 09:30:00'),
                'ends_at' => Carbon::parse('2024-10-12 12:30:00'),
                'type' => 'block',
            ],
            [
                'id' => null, // New record
                'starts_at' => Carbon::parse('2024-10-15 09:00:00'),
                'ends_at' => Carbon::parse('2024-10-15 12:00:00'),
                'type' => 'opening',
            ],
        ];

        $action = new ProcessScheduleOverrides;
        $updatedRecords = $action->execute(
            overrides: $overrides,
            resourceId: $resource->id,
            locationId: $location->id,
            month: '2024-10',
            timezone: 'UTC'
        );

        // Assert that both records exist in the database with correct attributes
        $this->assertCount(2, $updatedRecords);
        $this->assertDatabaseHas('schedule_overrides', [
            'id' => $existingOverride->id,
            'resource_id' => $resource->id,
            'location_id' => $location->id,
            'starts_at' => Carbon::parse('2024-10-12 09:30:00'),
            'ends_at' => Carbon::parse('2024-10-12 12:30:00'),
            'type' => 'block',
        ]);
        $this->assertDatabaseHas('schedule_overrides', [
            'resource_id' => $resource->id,
            'location_id' => $location->id,
            'starts_at' => Carbon::parse('2024-10-15 09:00:00'),
            'ends_at' => Carbon::parse('2024-10-15 12:00:00'),
            'type' => 'opening',
        ]);
    }
}
