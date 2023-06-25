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
        'meta'
    ];

    protected $dates = [
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'meta' => 'array',
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
        return $this->hasOne(Service::class);
    }
}
