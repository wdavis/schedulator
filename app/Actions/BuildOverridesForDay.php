<?php

namespace App\Actions;

use App\Actions\Imports\DailyHours;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Spatie\Period\Boundaries;
use Spatie\Period\Period;
use Spatie\Period\PeriodCollection;
use Spatie\Period\Precision;

// class is just used for syncing RA
class BuildOverridesForDay
{
    /**
     * @param CarbonImmutable $date
     * @param Collection<DailyHours> $overrides
     */
    public function build(CarbonImmutable $date, Collection $openings)
    {
        $formattedOpenings = [];

        foreach ($openings as $periods) {

            foreach($periods as $period) {
                $formattedOpenings[] = [
                    'type' => 'opening',
                    'starts_at' => CarbonImmutable::parse($period['starts_at'])->toIso8601String(),
                    'ends_at' => CarbonImmutable::parse($period['ends_at'])->toIso8601String()
                ];
            }
        }

        // create a range of 24 hours as a block
        $blockedPeriod = new Period(
            $date->startOfDay(),
            $date->endOfDay(),
            precision: Precision::MINUTE(),
            boundaries: Boundaries::EXCLUDE_NONE()
        );

        $collection = new PeriodCollection($blockedPeriod);

        foreach($openings as $opening) {
            foreach($opening as $period) {
                $collection = $collection->subtract(new Period(
                    CarbonImmutable::parse($period['starts_at']),
                    CarbonImmutable::parse($period['ends_at']),
                    precision: Precision::MINUTE(),
                    boundaries: Boundaries::EXCLUDE_NONE()
                ));
            }
        }

        $formattedBlocks = [];

        foreach($collection as $block) {
            $formattedBlocks[] = [
                'type' => 'block',
                'starts_at' => CarbonImmutable::parse($block->includedStart())->setTimezone('utc')->toIso8601String(),
                'ends_at' => CarbonImmutable::parse($block->includedEnd())->setTimezone('utc')->toIso8601String()
            ];
        }

        return [
            'opening' => $formattedOpenings,
            'block' => $formattedBlocks // todo are providers actually using recurring schedules?
        ];
    }
}
