<?php

namespace App\Http\Controllers\Api;

use Carbon\CarbonImmutable;

class DateController
{
    public function index()
    {
        $first = CarbonImmutable::today('America/Chicago')->startOfDay();
        $last = CarbonImmutable::today('America/Chicago')->endOfDay();

        return [
            'now' => CarbonImmutable::now()->toIso8601String(),
            'todayStart' => CarbonImmutable::now()->startOfDay()->toIso8601String(),
            'todayEnd' => CarbonImmutable::now()->endOfDay()->toIso8601String(),
            'nowCt' => CarbonImmutable::now('America/Chicago')->toIso8601String(),
            'todayStartCt' => $first->setTimezone('UTC')->toIso8601String(),
            'todayEndCt' => $last->setTimezone('UTC')->toIso8601String()
        ];
    }
}
