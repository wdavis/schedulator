<?php

namespace App\Actions;

use App\Models\ScheduleOverride;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Spatie\Period\Period;
use Spatie\Period\PeriodCollection;
use Spatie\Period\Precision;

class BuildScheduleOverrides
{
    public function get(Collection $overrides)
    {
        // Create empty PeriodCollections for 'opening' and 'block'
        $openingOverrides = new PeriodCollection();
        $blockOverrides = new PeriodCollection();

        foreach ($overrides as $override) {
            // Create a Period for the current ScheduleOverride
            $start = CarbonImmutable::createFromFormat('Y-m-d H:i:s', $override->starts_at);
            $end = CarbonImmutable::createFromFormat('Y-m-d H:i:s', $override->ends_at);
            $period = Period::make($start, $end, Precision::MINUTE());

            // Add the Period to the appropriate collection based on the type
            if ($override->type === 'opening') {
                $openingOverrides = $openingOverrides->add($period);
            } elseif ($override->type === 'block') {
                $blockOverrides = $blockOverrides->add($period);
            }
        }

        return [
            'opening' => $openingOverrides,
            'block' => $blockOverrides,
        ];
    }

    /** @deprecated  */
    public function build(CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        // Retrieve ScheduleOverride records between the specified dates
        $overrides = ScheduleOverride::whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->get();

        // Create empty PeriodCollections for 'opening' and 'block'
        $openingOverrides = new PeriodCollection();
        $blockOverrides = new PeriodCollection();

        foreach ($overrides as $override) {
            // Create a Period for the current ScheduleOverride
            $start = CarbonImmutable::createFromFormat('Y-m-d H:i:s', $override->date . ' ' . $override->start_time);
            $end = CarbonImmutable::createFromFormat('Y-m-d H:i:s', $override->date . ' ' . $override->end_time);
            $period = Period::make($start, $end);

            // Add the Period to the appropriate collection based on the type
            if ($override->type === 'opening') {
                $openingOverrides = $openingOverrides->add($period);
            } elseif ($override->type === 'block') {
                $blockOverrides = $blockOverrides->add($period);
            }
        }

        return [
            'opening' => $openingOverrides,
            'block' => $blockOverrides,
        ];
    }
}
