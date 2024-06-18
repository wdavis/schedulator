<?php

namespace App\Actions\Imports;

use App\Actions\Imports\TimeRange;

class DailyHours
{
    public $overridden = false;

    public $date;

    public $hours = [];

    /**
     * @var bool not editable
     */
    public $locked;

    /**
     * @var bool hours are not visible -- owned by another month
     */
    public $hidden;

    /**
     * DailyHours constructor.
     */
    public function __construct(string $date, array $hours, bool $locked = false, bool $hidden = false, bool $overridden = false)
    {
        $this->date = $date;
        $this->setHours($hours);
        $this->overridden = $overridden;
        $this->locked = $locked;
        $this->hidden = $hidden;
    }

    private function setHours(array $hours)
    {
        if (isset($hours[0])) {
            if ($hours[0] === '' || $hours[0] == 'Closed') { //acuity sets first range to '' if nothing has been set
                $this->hours = [];
            } else {
                $this->hours = $this->getAllRangesAsOneFlatString($hours);
            }
        }
    }

    /**
     * @param  TimeRange[]  $ranges
     */
    private function getAllRangesAsOneFlatString(array $ranges): array
    {
        return array_map(function ($range) {
            return new TimeRange($range);
        }, $ranges);
    }
}
