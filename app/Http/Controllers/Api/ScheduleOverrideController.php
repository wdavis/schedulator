<?php

namespace App\Http\Controllers\Api;

use App\Actions\FormatOverrides;
use App\Actions\FormatValidationErrors;
use App\Actions\Overrides\CreateOverride;
use App\Actions\ProcessScheduleOverrides;
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

    private FormatOverrides $formatOverrides;

    private ProcessScheduleOverrides $processScheduleOverrides;

    public function __construct(CreateOverride $createOverride, FormatValidationErrors $formatValidationErrors, FormatOverrides $formatOverrides, \App\Actions\ProcessScheduleOverrides $processScheduleOverrides)
    {
        $this->createOverride = $createOverride;
        $this->formatValidationErrors = $formatValidationErrors;
        $this->formatOverrides = $formatOverrides;
        $this->processScheduleOverrides = $processScheduleOverrides;
    }

    public function index(string $resourceId)
    {
        // validate the request
        $validator = Validator::make(request()->all(), [
            'starts_at' => ['required', new Iso8601Date],
            'ends_at' => ['required', new Iso8601Date],
            'timezone' => ['required', 'timezone'],
        ]);

        if ($validator->fails()) {
            return response()->json($this->formatValidationErrors->validate($validator->errors()->getMessages()), 422);
        }

        $startDate = CarbonImmutable::parse(request('starts_at'));
        $endDate = CarbonImmutable::parse(request('ends_at'));

        $timezone = request('timezone', 'UTC');

        $overrides = ScheduleOverride::where('resource_id', $resourceId)
            ->whereBetween('starts_at', [$startDate->toIso8601ZuluString(), $endDate->toIso8601ZuluString()])
            ->orderBy('starts_at')
            ->get();

        return $this->formatOverrides->format($overrides, $startDate, $endDate, $timezone);
    }

    public function store(string $resourceId)
    {
        $validator = Validator::make(request()->toArray(), [
            'type' => ['required', 'in:'.ScheduleOverrideType::opening->value.','.ScheduleOverrideType::block->value],
            'start_at' => ['required', new Iso8601Date],
            'end_at' => ['required', new Iso8601Date],
        ], [
            'type' => 'The type field must be one either '.ScheduleOverrideType::opening->value.' or '.ScheduleOverrideType::block->value,
        ]);

        if ($validator->fails()) {
            return $this->formatValidationErrors->validate($validator->errors()->getMessages());
        }

        $resource = Resource::where('id', $resourceId)
            ->where('environment_id', $this->getApiEnvironmentId())
            ->with('location')
            ->firstOrFail();

        $startDate = new CarbonImmutable(request('starts_at'));
        $endDate = new CarbonImmutable(request('ends_at'));

        // convert type string to enum
        $type = match (request('type')) {
            'block' => ScheduleOverrideType::block,
            'opening' => ScheduleOverrideType::opening
        };

        return $this->createOverride->create($resource, $type, $startDate, $endDate, []);
    }

    public function update(string $resourceId, string $month)
    {
        $validator1 = Validator::make(['month' => $month], [
            'month' => ['required', 'date_format:Y-m'],
        ]);

        if ($validator1->fails()) {
            return response()->json($this->formatValidationErrors->validate($validator1->errors()->getMessages()), 422);
        }

        $validator = Validator::make(request()->toArray(), [
            'timezone' => ['required', 'timezone'],
            'schedules.*.id' => ['nullable', 'uuid'],
            'schedules.*.type' => ['required', 'in:'.ScheduleOverrideType::opening->value.','.ScheduleOverrideType::block->value],
            'schedules.*.starts_at' => ['required', new Iso8601Date],
            'schedules.*.ends_at' => ['required', new Iso8601Date],
        ], [
            'schedules.*.type' => 'The type field must be one either '.ScheduleOverrideType::opening->value.' or '.ScheduleOverrideType::block->value,
        ]);

        if ($validator->fails()) {
            return response()->json($this->formatValidationErrors->validate($validator->errors()->getMessages()), 422);
        }

        $timezone = request('timezone', 'UTC');

        // verify the resource
        $resource = Resource::where('id', $resourceId)
            ->where('environment_id', $this->getApiEnvironmentId())
            ->with('location')
            ->firstOrFail();

        $updatedRecords = $this->processScheduleOverrides->execute(
            overrides: request('schedules'),
            resourceId: $resourceId,
            locationId: $resource->location->id,
            month: $month,
            timezone: $timezone
        );

        $formattedOverrides = $this->formatOverrides->format(
            scheduleOverrides: $updatedRecords,
            startDate: CarbonImmutable::createFromFormat('Y-m', $month, $timezone)->startOfMonth(),
            endDate: CarbonImmutable::createFromFormat('Y-m', $month, $timezone)->endOfMonth(),
            timezone: $timezone
        );

        return response()->json($formattedOverrides);
    }

    public function destroy(string $resourceId, string $overrideId)
    {
        $override = ScheduleOverride::where('id', $overrideId)
            ->whereHas('resource', function ($query) use ($resourceId) {
                $query->where('id', $resourceId);
                $query->where('environment_id', $this->getApiEnvironmentId());
            })
            ->firstOrFail();

        $override->delete();

        return response()->json([
            'message' => 'Override deleted',
        ]);
    }
}
