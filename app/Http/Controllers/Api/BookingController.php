<?php

namespace App\Http\Controllers\Api;

use App\Actions\CreateBooking;
use App\Actions\FormatValidationErrors;
use App\Exceptions\BookingTimeSlotNotAvailableException;
use App\Rules\Iso8601Date;
use App\Rules\NotFromPast;
use App\Traits\InteractsWithEnvironment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BookingController
{
    use InteractsWithEnvironment;

    private CreateBooking $createBooking;
    private FormatValidationErrors $formatValidationErrors;

    public function __construct(CreateBooking $createBooking, FormatValidationErrors $formatValidationErrors)
    {
        $this->createBooking = $createBooking;
        $this->formatValidationErrors = $formatValidationErrors;
    }

    public function post(Request $request, string $resourceId)
    {
        // todo how do we scope to the proper environment?

        // maybe we create a new class that provides the user, environment and resource/resource id
        // and then we can use that to scope the query

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
}
