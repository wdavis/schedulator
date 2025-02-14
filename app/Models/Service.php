<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasUuids;
    use HasFactory;

    protected $fillable = [
        'name',
        'duration',
        'buffer_before',
        'buffer_after',
        'booking_window_lead',
        'booking_window_end',
        'cancellation_window_end',
//        'booking_id',
//        'environment_id',
    ];

    protected $appends = [
//        'buffer_before_interval',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function environment()
    {
        return $this->belongsTo(Environment::class);
    }

//    public function bufferBeforeInterval(): Attribute
//    {
//        return Attribute::make(
//            get: function ($value, $attributes) {
//                try {
//                    $interval = new \DateInterval("PT{$attributes['buffer_before']}M");
//                } catch (\Exception $e) {
//                    // Handle exception as needed.
//                    $interval = null;
//                }
//
//                return $interval;
//            },
//        )->withoutObjectCaching();
//    }
}
