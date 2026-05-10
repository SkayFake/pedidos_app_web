<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'zone_id'    => ['sometimes', 'integer', 'exists:zones,id'],
            'label'      => ['sometimes', 'string', 'max:50'],
            'street'     => ['sometimes', 'string', 'max:255'],
            'references' => ['nullable', 'string', 'max:255'],
            'latitude'   => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'  => ['nullable', 'numeric', 'between:-180,180'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'zone_id.exists'  => 'La zona seleccionada no existe.',
            'label.max'       => 'La etiqueta no puede exceder 50 caracteres.',
            'street.max'      => 'La dirección no puede exceder 255 caracteres.',
            'references.max'  => 'Las referencias no pueden exceder 255 caracteres.',
        ];
    }

    /**
     * Retornar errores de validación como JSON.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Error de validación.',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
