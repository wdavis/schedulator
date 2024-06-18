<?php

namespace App\Http\Controllers\Api;

use App\Actions\FormatValidationErrors;
use App\Actions\Overrides\CreateOverride;
use App\Enums\ScheduleOverrideType;
use App\Models\Resource;
use App\Models\ScheduleOverride;
use App\Rules\Iso8601Date;
use App\Traits\InteractsWithEnvironment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;

class ScheduleOverrideController
{
    use InteractsWithEnvironment;

    private CreateOverride $createOverride;
    private FormatValidationErrors $formatValidationErrors;

    /**
     * @param CreateOverride $createOverride
     * @param FormatValidationErrors $formatValidationErrors
     */
    public function __construct(CreateOverride $createOverride, FormatValidationErrors $formatValidationErrors)
    {
        $this->createOverride = $createOverride;
        $this->formatValidationErrors = $formatValidationErrors;
    }

    public function index(string $resourceId)
    {
        return ScheduleOverride::where('resource_id', $resourceId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function store(string $resourceId)
    {
        $validator = Validator::make(request()->toArray(), [
            'type' => ['required', 'in:block,opening'],
            'start_date' => ['required', new Iso8601Date()],
            'end_date' => ['required', new Iso8601Date()]
        ], [
            'type' => 'The type field must be one either block or opening'
        ]);

        if($validator->fails()) {
            return $this->formatValidationErrors->validate($validator->errors()->getMessages());
        }

        $resource = Resource::where('id', $resourceId)
            ->where('environment_id', $this->getApiEnvironmentId())
            ->with('location')
            ->firstOrFail();

        $startDate = new CarbonImmutable(request('start_date'));
        $endDate = new CarbonImmutable(request('end_date'));

        // convert type string to enum
        $type = match(request('type')) {
            'block' => ScheduleOverrideType::block,
            'opening' => ScheduleOverrideType::opening
        };

        return $this->createOverride->create($resource, $type, $startDate, $endDate, []);
    }

    public function destroy(string $resourceId, string $overrideId)
    {
        $override = ScheduleOverride::where('id', $overrideId)
            ->where('resource_id', $resourceId)
//            ->where('environment_id', $this->getApiEnvironmentId())
            ->firstOrFail();

        $override->delete();

        return response()->json([
            'message' => 'Override deleted'
        ]);
    }
}
