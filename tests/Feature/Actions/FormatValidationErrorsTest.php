<?php

namespace Tests\Feature\Actions;

use App\Actions\FormatValidationErrors;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class FormatValidationErrorsTest extends TestCase
{
    public function test_messages_look_correct(): void
    {
        $data = [
            'endDate' => '',
            'resource' => '',
            'serviceId' => '',
        ];

        $rules = [
            'endDate' => 'required',
            'resource' => 'required',
            'serviceId' => 'required',
        ];

        $validator = Validator::make($data, $rules);

        $errors = $validator->errors()->getMessages();

        $expected = [
            'validationErrors' => [
                ['key' => 'endDate', 'message' => 'The end date field is required.'],
                ['key' => 'resource', 'message' => 'The resource field is required.'],
                ['key' => 'serviceId', 'message' => 'The service id field is required.'],
            ]
        ];

        $formatValidationErrors = new FormatValidationErrors();

        $result = $formatValidationErrors->validate($errors);

        $this->assertEquals($expected, $result);
    }
}
