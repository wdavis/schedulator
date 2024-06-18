<?php

namespace App\Actions\Imports;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;

class TimeRange implements Arrayable
{
    public $startTimeHour = '';

    public $startTimeMinute = '';

    public $startTimeSlot = '';

    public $endTimeHour = '';

    public $endTimeMinute = '';

    public $endTimeSlot = '';

    /**
     * TimeRange constructor.
     *
     * @param  string|array  $input
     */
    public function __construct($input)
    {
        if (is_array($input)) {
            if (isset($input['startTimeHour'])) {
                $this->startTimeHour = $input['startTimeHour'];
            }
            if (isset($input['startTimeMinute'])) {
                $this->startTimeMinute = $input['startTimeMinute'];
            }
            if (isset($input['startTimeSlot'])) {
                $this->startTimeSlot = $input['startTimeSlot'];
            }

            if (isset($input['endTimeHour'])) {
                $this->endTimeHour = $input['endTimeHour'];
            }
            if (isset($input['endTimeMinute'])) {
                $this->endTimeMinute = $input['endTimeMinute'];
            }
            if (isset($input['endTimeSlot'])) {
                $this->endTimeSlot = $input['endTimeSlot'];
            }
        }

        if (is_string($input)) {
            $explosion = explode('-', $input);

            if (count($explosion) === 2) {
                $startTime = Carbon::parse($explosion[0]);
                $endTime = Carbon::parse($explosion[1]);

                $this->startTimeHour = $startTime->format('g');
                $this->startTimeMinute = $startTime->format('i');
                $this->startTimeSlot = $startTime->format('a');

                $this->endTimeHour = $endTime->format('g');
                $this->endTimeMinute = $endTime->format('i');
                $this->endTimeSlot = $endTime->format('a');
            }
        }
    }

    public function getFlatRange()
    {
        return "{$this->startTimeHour}:{$this->startTimeMinute}{$this->startTimeSlot}-{$this->endTimeHour}:{$this->endTimeMinute}{$this->endTimeSlot}";
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return [
            'startTimeHour' => $this->startTimeHour,
            'startTimeMinute' => $this->startTimeMinute,
            'startTimeSlot' => $this->startTimeSlot,
            'endTimeHour' => $this->endTimeHour,
            'endTimeMinute' => $this->endTimeMinute,
            'endTimeSlot' => $this->endTimeSlot,
        ];
    }
}
