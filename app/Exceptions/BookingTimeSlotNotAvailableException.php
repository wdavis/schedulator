<?php

namespace App\Exceptions;

use App\Contracts\HttpStatusContract;

class BookingTimeSlotNotAvailableException extends \Exception implements HttpStatusContract
{
    public function getHttpStatusCode(): int
    {
        return 409;
    }
}
