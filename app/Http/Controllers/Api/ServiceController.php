<?php

namespace App\Http\Controllers\Api;

use App\Actions\FormatValidationErrors;
use App\Actions\Services\CreateService;
use App\Actions\Services\UpdateService;
use App\Models\Service;
use App\Traits\InteractsWithEnvironment;

class ServiceController
{
    use InteractsWithEnvironment;

    private CreateService $createService;
    private UpdateService $updateService;
    private FormatValidationErrors $formatValidationErrors;

    /**
     * @param CreateService $createService
     * @param UpdateService $updateService
     * @param FormatValidationErrors $formatValidationErrors
     */
    public function __construct(CreateService $createService, \App\Actions\Services\UpdateService $updateService, \App\Actions\FormatValidationErrors $formatValidationErrors)
    {
        $this->createService = $createService;
        $this->updateService = $updateService;
        $this->formatValidationErrors = $formatValidationErrors;
    }

    public function index()
    {
        return Service::where('environment_id', $this->getApiEnvironmentId())->get();
    }

    public function store()
    {
        // validator
        $validator = validator(request()->all(), [
            'name' => 'required|string',
        ]);

        if($validator->fails()) {
            return $this->formatValidationErrors->validate($validator->errors()->getMessages());
        }

        return $this->createService->create(
            $this->getApiUser(),
            $this->getApiEnvironment(),
            request('name')
        );
    }

    public function update(string $id)
    {
        // validator
        $validator = validator(request()->all(), [
            'name' => ['string', 'nullable'],
            'duration' => ['integer', 'nullable'],
            'buffer_before' => ['integer', 'nullable'],
            'buffer_after' => ['integer', 'nullable'],
            'booking_window_lead' => ['integer', 'nullable'],
            'booking_window_end' => ['integer', 'nullable'],
            'cancellation_lead' => ['integer', 'nullable'],
        ]);

        if($validator->fails()) {
            return $this->formatValidationErrors->validate($validator->errors()->getMessages());
        }

        $service = Service::where('id', $id)
            ->where('environment_id', $this->getApiEnvironmentId())
            ->firstOrFail();

        return $this->updateService->update(
            $service,
            request()->all()
        );
    }

    public function destroy()
    {

    }
}
