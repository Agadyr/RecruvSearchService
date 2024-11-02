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
class SearchArticlesRequest extends FormRequest
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
            'description' => 'sometimes|string|max:255',
            'name' => 'sometimes|array',
            'price' => 'sometimes|numeric',
            'category' => 'sometimes|string',
            'sub_category' => 'sometimes|string',
            'colors' => 'sometimes|array',
            'sizes' => 'sometimes|array',
            'gender' => 'string|array',
            'sortByPrice' => 'nullable|boolean',
            'sortByName' => 'nullable|boolean'
        ];
    }
}
