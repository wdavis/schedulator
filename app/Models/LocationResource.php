<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class LocationResource extends Pivot
{
    use HasFactory;

    public $incrementing = false;
}
