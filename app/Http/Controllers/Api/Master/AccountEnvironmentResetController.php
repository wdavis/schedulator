<?php

namespace App\Http\Controllers\Api\Master;

use App\Models\Environment;
use App\Models\Resource;

class AccountEnvironmentResetController
{
    public function destroy(string $environmentId)
    {
        $environment = Environment::where('id', $environmentId)->firstOrFail();

        if(str_contains($environment->name, 'prod')) {
            return response()->json(['message' => 'Cannot reset production environment'], 422);
        }

        // delete all resources from the environment
        Resource::where('environment_id', $environment->id)->delete();

        // probably need to look for locations to delete

        return response()->json(['message' => 'Environment reset']);
    }
}
