<?php

namespace App\Traits;

trait InteractsWithEnvironment
{
    protected function getApiEnvironment()
    {
        return request()->attributes->get('environment');
    }

    protected function getApiUser()
    {
        return request()->attributes->get('user');
    }

    protected function getApiEnvironmentId()
    {
        return $this->getApiEnvironment()->id;
    }

    protected function getApiUserId()
    {
        return $this->getApiUser()->id;
    }
}
