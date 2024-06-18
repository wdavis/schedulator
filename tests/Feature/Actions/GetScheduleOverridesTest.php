<?php

namespace Tests\Feature\Actions;

use App\Actions\GetScheduleOverrides;
use App\Enums\ScheduleOverrideType;
use App\Models\Resource;
use App\Models\ScheduleOverride;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class GetScheduleOverridesTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_schedule_overrides()
    {
//        $resources =

        $resources = Resource::factory()->count(3)->create();

        $resourceIds = $resources->pluck('id')->toArray();
        $startDate = CarbonImmutable::now();
        $endDate = $startDate->addWeek();
        $environmentId = 'test-env';

        // Create some schedule overrides
        foreach ($resourceIds as $resourceId) {
            ScheduleOverride::factory()->create([
                'starts_at' => $startDate->startOfDay()->format('Y-m-d H:i:s'),
                'ends_at' => $endDate->endOfDay()->format('Y-m-d H:i:s'),
                'type' => ScheduleOverrideType::opening,
                'resource_id' => $resourceId,

            ]);
            ScheduleOverride::factory()->create([
                'starts_at' => $startDate->startOfDay()->format('Y-m-d H:i:s'),
                'ends_at' => $endDate->endOfDay()->format('Y-m-d H:i:s'),
                'type' => ScheduleOverrideType::block,
                'resource_id' => $resourceId
            ]);
        }

        $getScheduleOverrides = new GetScheduleOverrides();
        $result = $getScheduleOverrides->get($resourceIds, $startDate, $endDate, $environmentId);

        $this->assertCount(count($resourceIds) * 2, $result); // Assert we have correct number of overrides

        // Check that the correct types and resource_ids are present in the result
        foreach ($result as $override) {
            $this->assertContains($override->type, ['opening', 'block']);
            $this->assertContains($override->resource_id, $resourceIds);
        }
    }

    public function test_overlapping_schedule_overrides_are_included()
    {
        $resources = Resource::factory()->count(3)->create();

        $resourceIds = $resources->pluck('id')->toArray();
        $startDate = CarbonImmutable::now();
        $endDate = $startDate->addWeek();
        $overlapStartDate = $startDate->subDays(2); // 2 days before the start date
        $overlapEndDate = $endDate->addDays(2); // 2 days after the end date

        // Create some schedule overrides with overlapping dates
        foreach ($resourceIds as $resourceId) {
            ScheduleOverride::factory()->create([
                'starts_at' => $overlapStartDate->startOfDay()->format('Y-m-d H:i:s'),
                'ends_at' => $overlapEndDate->endOfDay()->format('Y-m-d H:i:s'),
                'type' => ScheduleOverrideType::opening,
                'resource_id' => $resourceId,
            ]);
        }

        $getScheduleOverrides = new GetScheduleOverrides();
        $result = $getScheduleOverrides->get($resourceIds, $startDate, $endDate);

        $this->assertCount(count($resourceIds), $result); // Assert we have correct number of overrides

        // Check that the correct types and resource_ids are present in the result
        foreach ($result as $override) {
            $this->assertContains($override->type, ['opening']);
            $this->assertContains($override->resource_id, $resourceIds);
        }
    }

    public function test_non_overlapping_schedule_overrides_are_excluded()
    {
        $resources = Resource::factory()->count(3)->create();

        $resourceIds = $resources->pluck('id')->toArray();
        $startDate = CarbonImmutable::now();
        $endDate = $startDate->addWeek();
        $nonOverlapStartDate = $endDate->addDays(2); // 2 days after the end date
        $nonOverlapEndDate = $nonOverlapStartDate->addDays(7); // 1 week after the non-overlapping start date

        // Create some schedule overrides with non-overlapping dates
        foreach ($resourceIds as $resourceId) {
            ScheduleOverride::factory()->create([
                'starts_at' => $nonOverlapStartDate->startOfDay()->format('Y-m-d H:i:s'),
                'ends_at' => $nonOverlapEndDate->endOfDay()->format('Y-m-d H:i:s'),
                'type' => ScheduleOverrideType::opening,
                'resource_id' => $resourceId,
            ]);
        }

        $getScheduleOverrides = new GetScheduleOverrides();
        $result = $getScheduleOverrides->get($resourceIds, $startDate, $endDate);

        $this->assertCount(0, $result); // Assert no overrides were returned
    }
}
