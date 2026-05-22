<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id'                 => ['required', 'integer', 'exists:branches,id'],
            'address_id'                => ['required', 'integer', 'exists:customer_addresses,id'],
            'lat'                       => ['nullable', 'numeric'],
            'lng'                       => ['nullable', 'numeric'],
            'coupon_code'               => ['nullable', 'string', 'max:50', 'exists:coupons,code'],
            'use_loyalty_points'        => ['nullable', 'boolean'],
            'notes'                     => ['nullable', 'string', 'max:500'],
            'items'                     => ['required', 'array', 'min:1'],
            'items.*.product_id'        => ['required', 'integer', 'exists:products,id'],
            'items.*.variant_id'        => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.quantity'          => ['required', 'integer', 'min:1', 'max:20'],
            'items.*.extras'            => ['nullable', 'array'],
            'items.*.extras.*.extra_id' => ['required', 'integer', 'exists:product_extras,id'],
            'items.*.extras.*.quantity' => ['required', 'integer', 'min:1', 'max:5'],
        ];
    }

    public function messages(): array
    {
        return [
            'branch_id.required'               => 'La sucursal es obligatoria.',
            'branch_id.exists'                 => 'La sucursal seleccionada no existe.',
            'address_id.required'              => 'La dirección de entrega es obligatoria.',
            'address_id.exists'                => 'La dirección seleccionada no existe.',
            'coupon_code.exists'               => 'El código de cupón no es válido.',
            'items.required'                   => 'Debes agregar al menos un producto al pedido.',
            'items.min'                        => 'Debes agregar al menos un producto al pedido.',
            'items.*.product_id.required'      => 'Cada ítem debe tener un producto.',
            'items.*.product_id.exists'        => 'Uno de los productos seleccionados no existe.',
            'items.*.variant_id.exists'        => 'Una de las variantes seleccionadas no existe.',
            'items.*.quantity.required'        => 'La cantidad es obligatoria para cada ítem.',
            'items.*.quantity.min'             => 'La cantidad mínima por ítem es 1.',
            'items.*.quantity.max'             => 'La cantidad máxima por ítem es 20.',
            'items.*.extras.*.extra_id.exists' => 'Uno de los extras seleccionados no existe.',
            'items.*.extras.*.quantity.min'    => 'La cantidad mínima de extras es 1.',
            'items.*.extras.*.quantity.max'    => 'La cantidad máxima de extras es 5.',
            'notes.max'                        => 'Las notas no pueden exceder 500 caracteres.',
        ];
    }

    /**
     * Retornar errores de validación como JSON.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Error de validación en el pedido.',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
