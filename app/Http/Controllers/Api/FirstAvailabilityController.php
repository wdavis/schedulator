<?php

namespace App\Http\Controllers\Api;

use App\Actions\FormatValidationErrors;
use App\Actions\GetFirstAvailableResource;
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
        $validator = Validator::make(request()->all(), [
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
            ->get();

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

            return response()->json($resources->firstWhere('id', $firstResourceId));
        } catch (\Exception $e) {

            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }


    }
}
