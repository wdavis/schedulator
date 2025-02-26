<?php

namespace App\Actions;

use App\Enums\ScheduleOverrideType;
use App\Models\ScheduleOverride;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class GetScheduleOverrides
{
    /**
     * @return Collection<ScheduleOverride>
     */
    public function get(array $resourceIds, CarbonImmutable $startDate, CarbonImmutable $endDate, ?string $environmentId = null): Collection
    {
        // we need to add a day to the start date and subtract a day from the end date
        // because the query uses the 'overlaps' operator, which is inclusive
        // so if the start date is 2021-01-01 and the end date is 2021-01-31
        // the query will return records where the date is 2021-01-01 or 2021-01-31
        // but we want to exclude those dates
        $overrides = ScheduleOverride::whereRaw('(starts_at, ends_at) overlaps (?, ?)', [$startDate->format('Y-m-d H:i:s'), $endDate->format('Y-m-d H:i:s')])
            ->whereIn('type', [ScheduleOverrideType::opening, ScheduleOverrideType::block]) // todo allow passing in types
            ->whereIn('resource_id', $resourceIds)
            ->get();

        return $overrides;
    }
}
