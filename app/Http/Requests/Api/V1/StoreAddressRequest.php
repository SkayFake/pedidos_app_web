<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'zone_id'    => ['required', 'integer', 'exists:zones,id'],
            'label'      => ['required', 'string', 'max:50'],
            'street'     => ['required', 'string', 'max:255'],
            'references' => ['nullable', 'string', 'max:255'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'zone_id.required'  => 'La zona es obligatoria.',
            'zone_id.exists'    => 'La zona seleccionada no existe.',
            'label.required'    => 'La etiqueta de la dirección es obligatoria (ej: Casa, Trabajo).',
            'label.max'         => 'La etiqueta no puede exceder 50 caracteres.',
            'street.required'   => 'La dirección de la calle es obligatoria.',
            'street.max'        => 'La dirección no puede exceder 255 caracteres.',
            'references.max'    => 'Las referencias no pueden exceder 255 caracteres.',
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
