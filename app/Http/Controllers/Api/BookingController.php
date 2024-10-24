<?php

namespace App\Http\Controllers\Api;

use App\Actions\Bookings\CancelBooking;
use App\Actions\Bookings\CreateBooking;
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
    private FormatValidationErrors $formatValidationErrors;

    public function __construct(CreateBooking $createBooking, FormatValidationErrors $formatValidationErrors, \App\Actions\Bookings\CancelBooking $cancelBooking)
    {
        $this->createBooking = $createBooking;
        $this->formatValidationErrors = $formatValidationErrors;
        $this->cancelBooking = $cancelBooking;
    }

    public function index(string $resourceId)
    {
        $bookings = Booking::whereHas('resource', function ($query) {
            $query->where('environment_id', $this->getApiEnvironmentId());
        })->where('resource_id', $resourceId)
            ->where(function($query) {
                $serviceId = request('serviceId', null);
                $locationId = request('locationId', null);
                if($serviceId) {
                    $query->where('service_id', $serviceId);
                }
                if($locationId) {
                    $query->where('location_id', $locationId);
                }

                $startDate = request('start_date', null);
                $endDate = request('end_date', null);
                if($startDate) {
                    // parse the date and convert to UTC
                    $startDate = \Carbon\CarbonImmutable::parse($startDate)->setTimezone('UTC');
                    $query->where('starts_at', '>=', $startDate);
                }
                if($endDate) {
                    // parse the date and convert to UTC
                    $endDate = \Carbon\CarbonImmutable::parse($endDate)->setTimezone('UTC');
                    $query->where('ends_at', '<=', $endDate);
                }
            })->orderBy('created_at', 'desc')->get();

        return $bookings;
    }

    public function post(Request $request, string $resourceId)
    {
        // validate the request
        $validator = Validator::make($request->all(), [
            'serviceId' => 'required',
            'timeSlot' => ['required', new Iso8601Date(), new NotFromPast()],
            'force' => ['nullable', 'boolean'],
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
            bypassLeadTime: $request->input('force', false),
        );
    }

    public function destroy(Request $request, string $id)
    {
        $booking = Booking::whereHas('resource', function ($query) {
            $query->where('environment_id', $this->getApiEnvironmentId());
        })->where('id', $id)->delete();

//        try {

        return response()->json([], 204);
//        } catch () {
//            return response()->json([], 204);
//        }
    }

    public function update(string $id)
    {
        $booking = Booking::whereHas('resource', function ($query) {
            $query->where('environment_id', $this->getApiEnvironmentId());
        })->where('id', $id)->firstOrFail();

//        try {

        $booking->name = request('name');
        $booking->save();

//        } catch () {
//            return response()->json([], 204);
//        }

    }
}
