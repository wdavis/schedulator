<?php

namespace App\Http\Controllers\Api;

use App\Models\Resource;

class ResourceToggleController
{
    public function update(string $id, string $toggle)
    {
        $resource = Resource::where('id', $id)->firstOrFail();
        $resource->active = $toggle === 'active';
        $resource->save();

        $description = $resource->active ? 'active' : 'inactive';

        return response()->json([
            'message' => "Resource is now {$description}",
        ]);
    }
}
