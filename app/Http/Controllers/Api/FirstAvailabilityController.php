<?php

namespace App\Http\Controllers\Api;

use App\Actions\FormatValidationErrors;
use App\Actions\GetFirstAvailableResource;
use App\Exceptions\NoResourceAvailabilityForRequestedTimeException;
use App\Http\Resources\ResourceResource;
use App\Models\Resource;
use App\Models\Service;
use App\Rules\Iso8601Date;
use App\Traits\InteractsWithEnvironment;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;

class FirstAvailabilityController
{
    use InteractsWithEnvironment;

    private GetFirstAvailableResource $getFirstAvailableResource;
    private FormatValidationErrors $formatValidationErrors;

    public function __construct(GetFirstAvailableResource $getFirstAvailableResource, FormatValidationErrors $formatValidationErrors)
    {
        $this->getFirstAvailableResource = $getFirstAvailableResource;
        $this->formatValidationErrors = $formatValidationErrors;
    }

    public function index()
    {
        $validator = Validator::make(request()->only('resourceIds', 'serviceId', 'time'), [
            'resourceIds' => 'required|array',
            'serviceId' => 'required',
            'time' => ['required', new Iso8601Date()],
        ]);

        if($validator->fails()) {
            return response()->json($this->formatValidationErrors->validate($validator->errors()->getMessages()), 422);
        }

        $resourceIds = request('resourceIds', []);
        $serviceId = request('serviceId', null);
        $time = request('time', null);

        $resources = Resource::whereIn('id', $resourceIds)
            ->where('environment_id', $this->getApiEnvironmentId())
            ->where('active', true)
            ->with('locations.schedules')
            // keep the order of the resources as provided in the request, postgres specific
            ->orderByRaw(
                "array_position(ARRAY[" . implode(',', array_map(fn($id) => "'{$id}'", $resourceIds)) . "]::uuid[], id)"
            )->get();

        try {
            try {
                $service = Service::where('id', $serviceId)->firstOrFail();
            } catch (ModelNotFoundException $e) {
                return response()->json([
                    'message' => 'Service not found'
                ], 404);
            }
            $requestedDate = CarbonImmutable::parse($time);

            $firstResourceId = $this->getFirstAvailableResource->get($resources, $service, $requestedDate);

            return ResourceResource::make($resources->firstWhere('id', $firstResourceId));
        } catch (NoResourceAvailabilityForRequestedTimeException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }


    }
}
