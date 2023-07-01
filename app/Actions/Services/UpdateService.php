<?php

namespace App\Actions\Services;

use App\Models\Service;

class UpdateService
{
    public function update(Service $service, array $updates): Service
    {
        $service->fill($updates);
        $service->save();

        return $service;
    }
}
