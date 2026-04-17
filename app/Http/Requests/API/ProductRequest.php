<?php

namespace App\Http\Requests\API;

use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|max:191',
            'description' => 'nullable|string|max:16777215',
            'name_en' => 'nullable|max:191',
            'description_en' => 'nullable|string|max:16777215',
            'cost' => 'nullable|numeric',
            'price' => 'nullable|numeric',
            'minimum' => 'nullable|numeric',
            'sku' => 'nullable|max:100',
            'barcode' => 'nullable|max:100',
            'show_price' => 'required|boolean',
            'is_featured' => 'required|boolean',            
        ];
    }

     /**
     * Custom message for validation
     *
     * @return array
     */
    public function messages()
    {
        return [
            'name.required' => 'Nombre es requerido',
            'name.max' => 'Nombre debe tener máximo 191 caracteres.',
            'name_en.max' => 'Nombre en inglés debe tener máximo 191 caracteres.',
            'description.max' => 'Descripción debe tener máximo 16777215 caracteres.',
            'description_en.max' => 'Descripción en inglés debe tener máximo 16777215 caracteres.',
            'cost.numeric' => 'Costo debe ser numerico',
            'price.numeric' => 'Precio debe ser numerico',
            'minimu.numeric' => 'Precio debe ser numerico',
            'sku.max' => 'SKU debe tener máximo 100 caracteres.',
            'barcode.max' => 'Barcode debe tener máximo 100 caracteres.',
            'show_price.boolean' => 'Valor debe ser numérico',
            'is_featured.boolean' => 'Valor debe ser numérico',            
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator $validator
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function failedValidation(Validator $validator)
    {
        $errors = (new ValidationException($validator))->errors();
        throw new HttpResponseException(
            response()->json(['errors' => $errors], 422)
        );
    }
}