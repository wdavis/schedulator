<?php

namespace App\Actions\Resources;

use App\Models\Resource;

class UpdateResource
{
    public function update(
        Resource $resource,
        ?string $name = null,
        ?int $bookingWindowLeadOverride = null,
        ?int $bookingWindowEndOverride = null,
        ?int $cancellationWindowEndOverride = null,
        array $meta = []): Resource
    {
        if($name) {
            $resource->name = $name;
        }

        // todo fix
        $resource->booking_window_lead_override = $bookingWindowLeadOverride;
//        $resource->booking_window_end_override = $bookingWindowEndOverride;
//        $resource->cancellation_window_end_override = $cancellationWindowEndOverride;

        $resource->meta = array_merge($resource->meta ?? [], $meta);

        $resource->save();

        return $resource;
    }
}
