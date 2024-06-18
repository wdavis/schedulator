<?php

namespace App\Actions\Overrides;

use App\Enums\ScheduleOverrideType;
use App\Models\Resource;
use App\Models\ScheduleOverride;
use Carbon\CarbonImmutable;

class CreateOverride
{
    public function create(Resource $resource, ScheduleOverrideType $type, CarbonImmutable $startDate, CarbonImmutable $endDate, array $data = [])
    {
        return ScheduleOverride::create([
            'resource_id' => $resource->id,
            'location_id' => $resource->location->id,
            'type' => $type,
            'starts_at' => $startDate,
            'ends_at' => $endDate,
        ]);
    }
}
