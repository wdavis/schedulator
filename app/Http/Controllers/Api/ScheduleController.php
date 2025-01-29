<?php

namespace App\Http\Controllers\Api;

use App\Actions\FormatSchedules;
use App\Actions\FormatValidationErrors;
use App\Actions\UpdateSchedule;
use App\Models\Location;
use App\Models\Resource;
use App\Traits\InteractsWithEnvironment;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;

class ScheduleController
{
    use InteractsWithEnvironment;

    private FormatSchedules $formatSchedules;

    private UpdateSchedule $updateSchedule;

    private FormatValidationErrors $formatValidationErrors;

    public function __construct(UpdateSchedule $updateSchedule, FormatValidationErrors $formatValidationErrors, \App\Actions\FormatSchedules $formatSchedules)
    {
        $this->updateSchedule = $updateSchedule;
        $this->formatValidationErrors = $formatValidationErrors;
        $this->formatSchedules = $formatSchedules;
    }

    public function index(string $resource_id)
    {
        try {
            $resource = Resource::where('id', $resource_id)
                ->where('environment_id', $this->getApiEnvironmentId())
                ->with('location.schedules')
                ->firstOrFail();

            $schedules = $resource->location?->schedules ?? [];

            // if schedule is empty, return an empty week schedule
            if (count($schedules) === 0) {
                return response()->json([
                    'monday' => [],
                    'tuesday' => [],
                    'wednesday' => [],
                    'thursday' => [],
                    'friday' => [],
                    'saturday' => [],
                    'sunday' => [],
                ]);
            }

            return $this->formatSchedules->format($schedules);

        } catch (ModelNotFoundException $e) {
            // report?
            return response()->json([
                'error' => "Resource {$resource_id} not found",
            ], 500);
        } catch (\Throwable $e) {
            // report?
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }

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
        ], [
            'schedules.*.*.end_time.after' => 'The end time must be after the start time.',
        ]);

        if ($validator->fails()) {
            return response()->json($this->formatValidationErrors->validate($validator->errors()->getMessages()), 422);
        }

        $location = null;
        if (request()->has('location_id')) {
            $location = Location::where('resource_id', $resource->id)->findOrFail(request()->get('location_id'));
        }

        return $this->updateSchedule->execute(
            $resource,
            request()->get('schedules'),
            $location,
        );
    }
}
