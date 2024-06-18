<?php

namespace App\Actions\Resources;

use App\Models\Location;
use App\Models\Resource;

class CreateResource
{
    public function create(string $name, string $environmentId, bool $active = false, array $meta = [])
    {
        $resource = new Resource();
        $resource->name = $name;
        $resource->environment_id = $environmentId;
        $resource->active = $active;
        $resource->meta = $meta;

        $resource->save();

        // todo create primary location
        $location = new Location();
        $location->name = 'Primary';
        // todo rerun migrations
        $location->primary = true;

        $resource->locations()->save($location);

        // todo create primary lcoation schedule

        $resource->load('locations');

        return $resource;
    }
}
