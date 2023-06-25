<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduleOverride extends Model
{
    use HasFactory;

    protected $fillable = [
        'resource_id',
        'location_id',
        'starts_at',
        'ends_at',
        'date',
        'type'
    ];

    protected $dates = [
        'starts_at',
        'ends_at',
    ];

    public function resource()
    {
        return $this->belongsTo(Resource::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}
