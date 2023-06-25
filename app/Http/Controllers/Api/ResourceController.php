<?php

namespace App\Http\Controllers\Api;

use App\Actions\FormatValidationErrors;
use App\Actions\Resources\CreateResource;
use App\Models\Resource;
use App\Traits\InteractsWithEnvironment;
use Illuminate\Support\Facades\Validator;

class ResourceController
{
    use InteractsWithEnvironment;

    private CreateResource $createResource;
    private FormatValidationErrors $formatValidationErrors;

    public function __construct(CreateResource $createResource, \App\Actions\FormatValidationErrors $formatValidationErrors)
    {
        $this->createResource = $createResource;
        $this->formatValidationErrors = $formatValidationErrors;
    }

    public function index()
    {
        $perPage = request('perPage', 20);

        return Resource::where('environment_id', $this->getApiEnvironmentId())
            ->paginate($perPage);
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
            return $this->createResource->create(
                name: request()->input('name'),
                environmentId: $this->getApiEnvironmentId(),
                active: request()->input('active', false),
            );
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function update()
    {

    }

    public function destroy(string $id)
    {
        // delete a Resource

        // is this possible to do with all the constraints?

    }
}
