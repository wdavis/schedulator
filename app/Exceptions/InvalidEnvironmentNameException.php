<?php

namespace App\Exceptions;

use App\Contracts\HttpStatusContract;

class InvalidEnvironmentNameException extends \Exception implements HttpStatusContract
{
    public function getHttpStatusCode(): int
    {
        return 422;
    }
}
