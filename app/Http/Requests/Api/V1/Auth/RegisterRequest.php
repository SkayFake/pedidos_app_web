<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:100'],
            'email'    => ['required', 'string', 'email', 'max:150', 'unique:users,email'],
            'phone'    => ['required', 'string', 'max:20', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'       => 'El nombre es obligatorio.',
            'name.max'            => 'El nombre no puede exceder 100 caracteres.',
            'email.required'      => 'El correo electrónico es obligatorio.',
            'email.email'         => 'El formato del correo electrónico no es válido.',
            'email.unique'        => 'Este correo electrónico ya está registrado.',
            'phone.required'      => 'El número de teléfono es obligatorio.',
            'phone.unique'        => 'Este número de teléfono ya está registrado.',
            'password.required'   => 'La contraseña es obligatoria.',
            'password.min'        => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed'  => 'La confirmación de contraseña no coincide.',
        ];
    }

    /**
     * Retornar errores de validación como JSON (nunca HTML).
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
