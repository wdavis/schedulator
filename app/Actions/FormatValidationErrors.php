<?php

namespace App\Actions;

class FormatValidationErrors
{
    public function validate(array $errors): array
    {
        $formattedErrors = [];

        foreach ($errors as $field => $message) {
            $formattedErrors['validationErrors'][] = ['key' => $field, 'message' => implode(', ', $message)];
        }

        return $formattedErrors;
    }
}
