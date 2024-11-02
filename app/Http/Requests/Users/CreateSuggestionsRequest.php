<?php

namespace App\Http\Requests\Users;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class CreateSuggestionsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new ValidationException($validator, response()->json([
            'message' => 'Ошибка валидации',
            'errors' => $validator->errors()
        ],422));
    }

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'message_params' => 'required|string|max:255',
            'user_email' => 'required|string'
        ];
    }
}
