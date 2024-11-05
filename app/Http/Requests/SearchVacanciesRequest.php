<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

/**
 * @OA\Schema(
 *     schema="SearchArticlesRequest",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="description", type="string", example="Описание статьи"),
 *     @OA\Property(property="name", type="array", @OA\Items(type="string")),
 *     @OA\Property(property="price", type="number", format="float", example=19.99),
 *     @OA\Property(property="category", type="string", example="Одежда"),
 *     @OA\Property(property="sub_category", type="string", example="Верхняя одежда"),
 *     @OA\Property(property="colors", type="array", @OA\Items(type="string")),
 *     @OA\Property(property="sizes", type="array", @OA\Items(type="string")),
 *     @OA\Property(property="gender", type="array", @OA\Items(type="string")),
 *     @OA\Property(property="sortByPrice", type="boolean", example=1),
 *     @OA\Property(property="sortByName", type="boolean", example=0)
 * )
 */
class SearchVacanciesRequest extends FormRequest
{
    protected function failedValidation(Validator $validator)
    {
        throw new ValidationException($validator, response()->json([
            'message' => 'Ошибки валидации',
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
            'name' => 'sometimes|string|max:255',
            'salary_from' => 'sometimes|numeric',
            'salary_to' => 'sometimes|numeric',
            'salary_type' => 'sometimes|string',
            'address' => 'sometimes|string',
            'skills' => 'sometimes',
            'cityId' => 'sometimes|numeric',
            'specializationId' => 'sometimes|numeric',
            'employmentTypeId' => 'sometimes|numeric',
            'experienceId' => 'sometimes|numeric',
            'created_at' => 'sometimes|date',

            'sortByName' => 'sometimes',
            'sortBySalaryFrom' => 'sometimes',
            'sortByCreatedAt' => 'sometimes',
            'sortByUpdatedAt' => 'sometimes',
        ];
    }
}
