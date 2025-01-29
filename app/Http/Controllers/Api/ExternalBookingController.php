<?php

namespace App\Http\Controllers\Api;

use App\Models\Booking;
use App\Traits\InteractsWithEnvironment;

class ExternalBookingController
{
    use InteractsWithEnvironment;

    public function index()
    {
        // look up bookings by their meta->patient_id's or meta->appointment_id's
        $patientIds = request('patient_ids'); // array
        $appointmentIds = request('appointment_ids', null); // array

        if ($appointmentIds) {
            $appointmentIds = collect($appointmentIds)->map(function ($appointmentId) {
                return (string) $appointmentId;
            })->toArray();
        }

        // use json wherein to search for the patient_id's and appointment_id's
        //        return Booking::whereHas('resource', function ($query) {
        //            $query->where('environment_id', $this->getApiEnvironmentId());
        //        })
        //            ->when($patientIds, function ($query) use ($patientIds) {
        //            $query->whereJsonContains('meta', ['patient_id' => $patientIds]);
        //        })
        return Booking::when($appointmentIds, function ($query) use ($appointmentIds) {
            // where raw (meta->>'appointment_id')::int IN (1106, 918)
            // cast all appointment_id's to text and implode them
            $query->whereRaw("(meta->>'appointment_id')::int IN (".implode(',', $appointmentIds).')');
        })->get();
    }

    public function update($bookingId)
    {
        $booking = Booking::find($bookingId);
        $booking->name = request('name');
        $booking->save();

        return $booking;

    }
}
