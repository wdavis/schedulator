<?php

namespace App\Http\Controllers\Api;

use App\Actions\FormatValidationErrors;
use App\Actions\Resources\CreateResource;
use App\Actions\Resources\UpdateResource;
use App\Http\Resources\ResourceResource;
use App\Models\Resource;
use App\Traits\InteractsWithEnvironment;
use Illuminate\Support\Facades\Validator;

class ResourceController
{
    use InteractsWithEnvironment;

    private CreateResource $createResource;
    private UpdateResource $updateResource;
    private FormatValidationErrors $formatValidationErrors;

    public function __construct(CreateResource $createResource, \App\Actions\FormatValidationErrors $formatValidationErrors, \App\Actions\Resources\UpdateResource $updateResource)
    {
        $this->createResource = $createResource;
        $this->formatValidationErrors = $formatValidationErrors;
        $this->updateResource = $updateResource;
    }

    public function index()
    {
        $perPage = request('perPage', 20);

        return ResourceResource::collection(Resource::where('environment_id', $this->getApiEnvironmentId())
            ->when(request()->has('active'), function($query) {
                $query->where('active', request('active') === 'true' ? 't' : 'f');
            })
            ->paginate($perPage)
        );
    }

    public function store()
    {
        $validator = Validator::make(request()->all(), [
            'name' => 'required',
            'active' => 'boolean',
        ]);

        if($validator->fails()) {
            return response()->json($validator->errors()->getMessages(), 422);
        }

        try {
            return new ResourceResource($this->createResource->create(
                name: request()->input('name'),
                environmentId: $this->getApiEnvironmentId(),
                active: request()->input('active', false),
            ));
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(string $resourceId)
    {
        $validator = Validator::make(request()->all(), [
            'name' => 'required',
            'active' => 'boolean',
            'meta' => ['array', 'nullable'],
        ]);

        if($validator->fails()) {
            return response()->json($validator->errors()->getMessages(), 422);
        }

        try {
            $resource = Resource::where('id', $resourceId)
                ->where('environment_id', $this->getApiEnvironmentId())
                ->firstOrFail();

            return $this->updateResource->update(
                resource: $resource,
                name: request()->input('name'),
                meta: request()->input('meta', []),
            );

        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        // delete a Resource

        // is this possible to do with all the constraints?

    }
}
