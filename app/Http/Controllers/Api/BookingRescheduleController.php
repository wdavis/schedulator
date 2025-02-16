<?php

namespace App\Http\Controllers\Api;

use App\Actions\Bookings\RescheduleBooking;
use App\Actions\FormatValidationErrors;
use App\Rules\Iso8601Date;
use App\Rules\NotFromPast;
use App\Traits\InteractsWithEnvironment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BookingRescheduleController
{
    use InteractsWithEnvironment;

    private RescheduleBooking $rescheduleBooking;

    private FormatValidationErrors $formatValidationErrors;

    public function __construct(RescheduleBooking $rescheduleBooking, FormatValidationErrors $formatValidationErrors)
    {
        $this->rescheduleBooking = $rescheduleBooking;
        $this->formatValidationErrors = $formatValidationErrors;
    }

    public function store(Request $request, string $bookingId)
    {
        // validate the request
        $validator = Validator::make($request->all(), [
            'timeSlot' => ['required', new Iso8601Date, new NotFromPast],
            'resourceId' => 'required',
            'force' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json($this->formatValidationErrors->validate($validator->errors()->getMessages()), 422);
        }

        $environmentId = $this->getApiEnvironmentId();

        return $this->rescheduleBooking->reschedule(
            bookingId: $bookingId,
            newTimeSlot: $request->input('timeSlot'),
            environmentId: $environmentId,
            newResourceId: $request->input('resourceId'),
            newServiceId: $request->input('serviceId'),
            meta: $request->input('meta', []),
            force: $request->input('force', false),
        );
    }
}
