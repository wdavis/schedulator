<?php

namespace App\Exceptions;

use App\Contracts\HttpStatusContract;

class ResourceNotActiveException extends \Exception implements HttpStatusContract
{
    public function getHttpStatusCode(): int
    {
        return 400;
    }
}
