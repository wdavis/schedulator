<?php

namespace App\Actions;

use App\Models\Service;
use Carbon\CarbonImmutable;
use DateInterval;
use Spatie\Period\Boundaries;
use Spatie\Period\Period;
use Spatie\Period\PeriodCollection;
use Spatie\Period\Precision;

class SplitPeriodIntoIntervals
{
    public function execute(PeriodCollection $collection, Service $service): array
    {
        $slots = [];

        $interval = new DateInterval('PT'.$service->duration.'M');

        foreach($collection as $period) {

            $periods = $this->split($period, $interval);

            foreach ($periods as $p) {
                $slots[] = [
                    'start' => CarbonImmutable::parse($p->start()),
                    'end' => CarbonImmutable::parse($p->end())
                ];
            }
        }

        return $slots;
    }

    private function split(Period $period, DateInterval $interval): array
    {
        $start = $period->start();
        $end = $period->end();

        $periods = [];

        while ($start < $end) {
            $newEnd = (clone $start)->add($interval);

            if ($newEnd > $end) {
                $newEnd = $end;
            }

            $periods[] = Period::make($start, $newEnd, Precision::MINUTE(), Boundaries::EXCLUDE_NONE());

            $start = $newEnd;
        }

        return $periods;
    }
}
