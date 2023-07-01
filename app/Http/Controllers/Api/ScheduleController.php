<?php

namespace App\Http\Controllers\Api;

use App\Actions\FormatValidationErrors;
use App\Actions\UpdateSchedule;
use App\Models\Location;
use App\Models\Resource;
use App\Traits\InteractsWithEnvironment;
use Illuminate\Support\Facades\Validator;

class ScheduleController
{
    use InteractsWithEnvironment;

    private UpdateSchedule $updateSchedule;
    private FormatValidationErrors $formatValidationErrors;

    public function __construct(UpdateSchedule $updateSchedule, FormatValidationErrors $formatValidationErrors)
    {
        $this->updateSchedule = $updateSchedule;
        $this->formatValidationErrors = $formatValidationErrors;
    }

    public function index(string $resource_id)
    {
        return Resource::where('id', $resource_id)
            ->where('environment_id', $this->getApiEnvironmentId())
            ->with('location.schedules')
            ->firstOrFail();
    }

    public function update(Resource $resource)
    {
        // validate
        $validator = Validator::make(request()->all(), [
            'location_id' => 'nullable|exists:locations,id',
            'schedules' => 'required|array',
            'schedules.*' => 'array',
            'schedules.*.*.start_time' => 'required|date_format:H:i:s',
            'schedules.*.*.end_time' => 'required|date_format:H:i:s|after:schedules.*.*.start_time',
        ],[
            'schedules.*.*.end_time.after' => 'The end time must be after the start time.',
        ]);

        if($validator->fails()) {
            return response()->json($this->formatValidationErrors->validate($validator->errors()->getMessages()), 422);
        }

        $location = null;
        if(request()->has('location_id')) {
            $location = Location::where('resource_id', $resource->id)->findOrFail(request()->get('location_id'));
        }

        return $this->updateSchedule->execute(
            $resource,
            request()->get('schedules'),
            $location,
        );
    }
}
