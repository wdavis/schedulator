<?php

namespace App\Exceptions;

use App\Contracts\HttpStatusContract;

class BookingAlreadyCancelledException extends \Exception implements HttpStatusContract
{

    public function getHttpStatusCode(): int
    {
        return 400;
    }
}
