<?php

namespace App\Exceptions;

use App\Contracts\HttpStatusContract;

class NoResourceAvailabilityForRequestedTimeException extends \Exception implements HttpStatusContract
{
    public function getHttpStatusCode(): int
    {
        return 400;
    }
}
