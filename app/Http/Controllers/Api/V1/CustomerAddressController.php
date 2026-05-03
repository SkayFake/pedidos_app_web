<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreAddressRequest;
use App\Http\Requests\Api\V1\UpdateAddressRequest;
use App\Http\Resources\V1\CustomerAddressResource;
use App\Models\CustomerAddress;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * @group Direcciones del Cliente
 *
 * CRUD de direcciones de entrega del cliente autenticado.
 * La primera dirección creada se marca automáticamente como predeterminada.
 */
class CustomerAddressController extends Controller
{
    use ApiResponse;

    /**
     * Listar mis direcciones
     *
     * Retorna todas las direcciones del cliente autenticado,
     * ordenadas por predeterminada primero.
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Listado de direcciones.",
     *   "data": [
     *     {
     *       "id": 3,
     *       "label": "Casa",
     *       "street": "Col. Escalón, Calle 5 #42",
     *       "references": "Frente al parque",
     *       "is_default": true,
     *       "zone": { "id": 1, "name": "Zona Centro" }
     *     },
     *     {
     *       "id": 5,
     *       "label": "Trabajo",
     *       "street": "Blvd. Los Héroes, Edificio ABC, Piso 3",
     *       "references": null,
     *       "is_default": false,
     *       "zone": { "id": 2, "name": "Zona Norte" }
     *     }
     *   ]
     * }
     */
    public function index(): JsonResponse
    {
        $addresses = auth()->user()
            ->addresses()
            ->with('zone')
            ->orderByDesc('is_default')
            ->orderBy('label')
            ->get();

        return $this->success(
            CustomerAddressResource::collection($addresses),
            'Listado de direcciones.'
        );
    }

    /**
     * Crear dirección
     *
     * Crea una nueva dirección de entrega para el cliente.
     * Si es la primera dirección, se marca como predeterminada automáticamente.
     * Si se marca como predeterminada, las demás se desmarcan.
     *
     * @bodyParam zone_id integer required ID de la zona de entrega. Example: 1
     * @bodyParam label string required Etiqueta descriptiva (ej: Casa, Trabajo). Example: Casa
     * @bodyParam street string required Dirección de la calle. Example: Col. Escalón, Calle 5 #42
     * @bodyParam references string Referencias adicionales. Example: Frente al parque, portón negro
     * @bodyParam is_default boolean Marcar como dirección predeterminada. Example: true
     *
     * @response 201 {
     *   "success": true,
     *   "message": "Dirección creada exitosamente.",
     *   "data": {
     *     "id": 6,
     *     "label": "Casa",
     *     "street": "Col. Escalón, Calle 5 #42",
     *     "references": "Frente al parque, portón negro",
     *     "is_default": true,
     *     "zone": { "id": 1, "name": "Zona Centro" }
     *   }
     * }
     */
    public function store(StoreAddressRequest $request): JsonResponse
    {
        $user = auth()->user();
        $validated = $request->validated();

        // Si se marca como default, quitar default de las demás
        if (!empty($validated['is_default'])) {
            $user->addresses()->update(['is_default' => false]);
        }

        // Si es la primera dirección, hacerla default automáticamente
        if ($user->addresses()->count() === 0) {
            $validated['is_default'] = true;
        }

        $address = $user->addresses()->create($validated);
        $address->load('zone');

        return $this->success(
            new CustomerAddressResource($address),
            'Dirección creada exitosamente.',
            201
        );
    }

    /**
     * Actualizar dirección
     *
     * Actualiza una dirección existente del cliente. Solo se pueden actualizar
     * las propias direcciones. Soporta actualizaciones parciales.
     *
     * @urlParam address integer required ID de la dirección. Example: 6
     *
     * @bodyParam zone_id integer ID de la zona de entrega. Example: 2
     * @bodyParam label string Etiqueta descriptiva. Example: Oficina
     * @bodyParam street string Dirección de la calle. Example: Blvd. Los Héroes, Edificio XYZ
     * @bodyParam references string Referencias adicionales. Example: Piso 5, suite 501
     * @bodyParam is_default boolean Marcar como predeterminada. Example: false
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Dirección actualizada exitosamente.",
     *   "data": {
     *     "id": 6,
     *     "label": "Oficina",
     *     "street": "Blvd. Los Héroes, Edificio XYZ",
     *     "references": "Piso 5, suite 501",
     *     "is_default": false,
     *     "zone": { "id": 2, "name": "Zona Norte" }
     *   }
     * }
     * @response 403 {
     *   "success": false,
     *   "message": "No tienes permiso para editar esta dirección."
     * }
     */
    public function update(UpdateAddressRequest $request, CustomerAddress $address): JsonResponse
    {
        // Policy: solo el dueño puede editar
        if ($address->user_id !== auth()->id()) {
            return $this->error('No tienes permiso para editar esta dirección.', 403);
        }

        $validated = $request->validated();

        // Si se marca como default, quitar default de las demás
        if (!empty($validated['is_default'])) {
            auth()->user()->addresses()
                ->where('id', '!=', $address->id)
                ->update(['is_default' => false]);
        }

        $address->update($validated);
        $address->load('zone');

        return $this->success(
            new CustomerAddressResource($address),
            'Dirección actualizada exitosamente.'
        );
    }

    /**
     * Eliminar dirección
     *
     * Elimina una dirección del cliente. No se puede eliminar si tiene pedidos
     * activos asociados (pendientes, confirmados, en preparación, asignados o en camino).
     *
     * @urlParam address integer required ID de la dirección a eliminar. Example: 6
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Dirección eliminada exitosamente.",
     *   "data": null
     * }
     * @response 403 {
     *   "success": false,
     *   "message": "No tienes permiso para eliminar esta dirección."
     * }
     * @response 422 {
     *   "success": false,
     *   "message": "No puedes eliminar esta dirección porque tiene pedidos activos."
     * }
     */
    public function destroy(CustomerAddress $address): JsonResponse
    {
        // Policy: solo el dueño puede eliminar
        if ($address->user_id !== auth()->id()) {
            return $this->error('No tienes permiso para eliminar esta dirección.', 403);
        }

        // No permitir eliminar si tiene pedidos activos asociados
        $hasActiveOrders = $address->orders()
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->exists();

        if ($hasActiveOrders) {
            return $this->error('No puedes eliminar esta dirección porque tiene pedidos activos.', 422);
        }

        $address->delete();

        return $this->success(null, 'Dirección eliminada exitosamente.');
    }
}
