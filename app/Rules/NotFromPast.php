<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Carbon\Carbon;

class NotFromPast implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     * @return void
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
