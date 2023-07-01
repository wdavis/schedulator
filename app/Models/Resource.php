<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Period\Period;

class Resource extends Model
{
    use HasFactory;
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'recurring_schedule',
        'environment_id',
    ];

    public function locations(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Location::class)
            ->using(LocationResource::class)
            ->withTimestamps();
    }

    // get the first location
    public function location()
    {
        return $this->hasOneThrough(Location::class, LocationResource::class, 'resource_id', 'id', 'id', 'location_id');
    }

    public function environment()
    {
        return $this->belongsTo(Environment::class);
    }

    public function isAvailable($dateTime, $durationInMinutes)
    {
        // Get the combined schedule for the given date
        $combinedSchedule = $this->getCombinedScheduleForDate($dateTime->toDateString());

        // Get the bookings for the given date
        $bookings = $this->bookings()
            ->whereDate('start_time', $dateTime->toDateString())
            ->get();

        // Check if there's an overlapping time slot in the combined schedule
        $requestedStartTime = $dateTime;
        $requestedEndTime = $dateTime->clone()->addMinutes($durationInMinutes);
        $requestedPeriod = Period::make($requestedStartTime, $requestedEndTime);

        foreach ($combinedSchedule as $timeSlot) {
            $timeSlotPeriod = Period::make($timeSlot['start_time'], $timeSlot['end_time']);
            if ($requestedPeriod->overlapsWith($timeSlotPeriod)) {
                // Check if there's an overlapping booking
                $overlappingBooking = $bookings->first(function ($booking) use ($requestedStartTime, $requestedEndTime) {
                    return $booking->start_time->lt($requestedEndTime) && $booking->end_time->gt($requestedStartTime);
                });

                if (!$overlappingBooking) {
                    return true; // No overlapping booking found during the requested time slot
                }
            }
        }

        return false;
    }
}
