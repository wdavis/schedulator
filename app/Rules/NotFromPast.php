<?php

namespace App\Rules;

use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NotFromPast implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            // Parse the ISO 8601 date string
            $date = Carbon::parse($value);

            if ($date->isBefore(Carbon::today())) {
                $fail('The :attribute must not be a date in the past.');
            }

        } catch (\Throwable $e) {
            // probably couldn't parse date
        }
    }
}
