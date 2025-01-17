<?php

namespace App\Rules;

use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidateLeadTime implements ValidationRule
{
    private int $leadTimeInMinutes;

    private string $serviceName;

    public function __construct(string $serviceName, int $leadTimeInMinutes)
    {
        $this->leadTimeInMinutes = $leadTimeInMinutes;
        $this->serviceName = $serviceName;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $currentDateTime = CarbonImmutable::now();
        $appointmentDateTime = CarbonImmutable::parse($value)->subMinutes($this->leadTimeInMinutes);

        if (! $currentDateTime->greaterThan($appointmentDateTime)) {
            $fail("Booking for {$this->serviceName} must have {$this->leadTimeInMinutes} minute lead time.");
        }
    }
}
