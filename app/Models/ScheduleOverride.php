<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduleOverride extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'resource_id',
        'location_id',
        'starts_at',
        'ends_at',
        'type'
    ];

    protected $casts = [
        'starts_at' => 'immutable_datetime',
        'ends_at' => 'immutable_datetime',
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
