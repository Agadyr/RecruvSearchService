<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class SearchResumesRequest extends FormRequest
{
    protected function failedValidation(Validator $validator)
    {
        throw new ValidationException($validator, response()->json([
            'message' => 'Validation errors',
            'errors' => $validator->errors(),
        ], 422));
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => 'sometimes|numeric',
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'position' => 'sometimes|string|max:255',
            'salary' => 'sometimes|numeric',
            'cityId' => 'sometimes|numeric',
            'userId' => 'sometimes|numeric',
            'citizenship' => 'sometimes|numeric',

            'sortBySalary' => 'sometimes',
            'sortByCreatedAt' => 'sometimes',
            'sortByUpdatedAt' => 'sometimes',
        ];
    }
}
