<?php

namespace App\Http\Controllers\Api;

use App\Actions\Bookings\CancelBooking;
use App\Actions\CreateBooking;
use App\Actions\FormatValidationErrors;
use App\Exceptions\BookingTimeSlotNotAvailableException;
use App\Models\Booking;
use App\Rules\Iso8601Date;
use App\Rules\NotFromPast;
use App\Traits\InteractsWithEnvironment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BookingController
{
    use InteractsWithEnvironment;

    private CreateBooking $createBooking;
    private CancelBooking $cancelBooking;
    private FormatValidationErrors $formatValidationErrors;

    public function __construct(CreateBooking $createBooking, FormatValidationErrors $formatValidationErrors, \App\Actions\Bookings\CancelBooking $cancelBooking)
    {
        $this->createBooking = $createBooking;
        $this->formatValidationErrors = $formatValidationErrors;
        $this->cancelBooking = $cancelBooking;
    }

    public function post(Request $request, string $resourceId)
    {
        // validate the request
        $validator = Validator::make($request->all(), [
            'serviceId' => 'required',
            'timeSlot' => ['required', new Iso8601Date(), new NotFromPast()],
        ]);

        if($validator->fails()) {
            return response()->json($this->formatValidationErrors->validate($validator->errors()->getMessages()), 422);
        }

        return $this->createBooking->create(
            resourceId: $resourceId,
            serviceId: $request->input('serviceId'),
            timeSlot: $request->input('timeSlot'),
            environmentId: $this->getApiEnvironmentId(),
            name: $request->input('name'),
            meta: $request->input('meta', []),
        );
    }

    public function destroy(string $id)
    {
        $booking = Booking::whereHas('resource', function ($query) {
            $query->where('environment_id', $this->getApiEnvironmentId());
        })->where('id', $id)->firstOrFail();

        return $this->cancelBooking->cancel($booking, request('force', false));
    }
}
