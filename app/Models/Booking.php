<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Booking extends Model
{
    use HasFactory;
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'resource_id',
        'location_id',
        'service_id',
        'starts_at',
        'ends_at',
        'cancelled_at',
        'meta'
    ];

    protected $casts = [
        'meta' => 'array',
        'starts_at' => 'immutable_datetime',
        'ends_at' => 'immutable_datetime',
        'cancelled_at' => 'immutable_datetime'
    ];

    public function resource()
    {
        return $this->belongsTo(Resource::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
