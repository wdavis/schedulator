<?php

namespace App\Actions\Resources;

use App\Models\Resource;

class UpdateResource
{
    public function update(Resource $resource, ?string $name = null, array $meta = []): Resource
    {
        if($name) {
            $resource->name = $name;
        }

        $resource->meta = array_merge($resource->meta, $meta);

        $resource->save();

        return $resource;
    }
}
