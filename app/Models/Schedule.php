<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'day_of_week',
        'start_time',
        'end_time',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
