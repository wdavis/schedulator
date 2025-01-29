<?php

namespace Tests\Feature\Actions\Resources;

use App\Actions\Resources\UpdateResource;
use App\Models\Resource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateResourceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_updates_the_name_of_the_resource(): void
    {
        // Arrange
        $resource = Resource::factory()->create([
            'name' => 'Old Resource Name',
        ]);

        $action = new UpdateResource;

        // Act
        $updatedResource = $action->update($resource, 'New Resource Name');

        // Assert
        $this->assertEquals('New Resource Name', $updatedResource->name);
        $this->assertDatabaseHas('resources', [
            'id' => $resource->id,
            'name' => 'New Resource Name',
        ]);
    }

    /** @test */
    public function it_updates_the_booking_window_overrides(): void
    {
        // Arrange
        $resource = Resource::factory()->create([
            'booking_window_lead_override' => 10,
            'booking_window_end_override' => 20,
            'cancellation_window_end_override' => 30,
        ]);

        $action = new UpdateResource;

        // Act
        $updatedResource = $action->update(
            $resource,
            bookingWindowLeadOverride: 20,
            bookingWindowEndOverride: 30,
            cancellationWindowEndOverride: 40
        );

        // Assert
        $this->assertEquals(20, $updatedResource->booking_window_lead_override);
        $this->assertEquals(30, $updatedResource->booking_window_end_override);
        $this->assertEquals(40, $updatedResource->cancellation_window_end_override);
        $this->assertDatabaseHas('resources', [
            'id' => $resource->id,
            'booking_window_lead_override' => 20,
            'booking_window_end_override' => 30,
            'cancellation_window_end_override' => 40,
        ]);
    }

    /** @test */
    public function it_merges_the_meta_data(): void
    {
        // Arrange
        $resource = Resource::factory()->create([
            'meta' => ['foo' => 'bar'],
        ]);

        $action = new UpdateResource;

        // Act
        $updatedResource = $action->update(
            $resource,
            meta: ['baz' => 'qux']);

        // Assert
        $this->assertEquals(['foo' => 'bar', 'baz' => 'qux'], $updatedResource->meta);
        $this->assertDatabaseHas('resources', [
            'id' => $resource->id,
            'meta' => json_encode(['foo' => 'bar', 'baz' => 'qux']),
        ]);
    }

    /** @test */
    public function it_does_not_update_name_if_not_provided(): void
    {
        // Arrange
        $resource = Resource::factory()->create([
            'name' => 'Original Name',
        ]);

        $action = new UpdateResource;

        // Act
        $updatedResource = $action->update($resource, null);

        // Assert
        $this->assertEquals('Original Name', $updatedResource->name);
        $this->assertDatabaseHas('resources', [
            'id' => $resource->id,
            'name' => 'Original Name',
        ]);
    }
}
