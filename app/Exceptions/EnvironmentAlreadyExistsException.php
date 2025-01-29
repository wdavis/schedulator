<?php

namespace App\Exceptions;

use App\Contracts\HttpStatusContract;

class EnvironmentAlreadyExistsException extends \Exception implements HttpStatusContract
{
    public function getHttpStatusCode(): int
    {
        return 400;
    }
}
