<?php

namespace App\Contracts;

interface HttpStatusContract
{
    public function getHttpStatusCode(): int;
}
