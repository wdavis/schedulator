<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Period\Period;
use Spatie\Period\PeriodCollection;
use Spatie\Period\Precision;

class Location extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'name',
    ];

    public function resources(): BelongsToMany
    {
        return $this->belongsToMany(Resource::class);
    }

    public function resource(): HasOne
    {
        return $this->hasOne(Resource::class)->latestOfMany();
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function buildSchedule(CarbonImmutable $startDate, CarbonImmutable $endDate)
    {
        $schedules = $this->schedules;

        $itemsToAdd = [];

        foreach ($schedules as $schedule) {
            //            ray($schedule);
            $currentDate = $startDate->copy();

            while ($currentDate->dayOfWeekIso !== $schedule->day_of_week) {
                $currentDate = $currentDate->addDay();
            }
            //ray($currentDate);
            while ($currentDate->lte($endDate)) {
                $start = $currentDate->setTimeFromTimeString($schedule->start_time);
                $end = $currentDate->setTimeFromTimeString($schedule->end_time);

                $itemsToAdd[] = Period::make($start, $end, Precision::MINUTE());
                ray('adding period', $start, $end);

                $currentDate = $currentDate->addWeek();
            }
        }

        return (new PeriodCollection(...$itemsToAdd))->overlapAll();

        //        return $periods;
    }
}
