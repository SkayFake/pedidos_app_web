<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Models\Deliveryman;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * @group Autenticación de Repartidor
 *
 * Endpoints para inicio de sesión y gestión de la sesión
 * de los repartidores en la app móvil.
 */
class DeliveryAuthController extends Controller
{
    use ApiResponse;

    /**
     * Iniciar sesión (Repartidor)
     *
     * Autentica al repartidor y emite un token Sanctum.
     *
     * @unauthenticated
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $deliveryman = Deliveryman::where('email', $request->email)->first();

        if (!$deliveryman || !Hash::check($request->password, $deliveryman->password)) {
            return $this->error('Credenciales incorrectas.', 401);
        }

        if (!$deliveryman->is_active) {
            return $this->error('Tu cuenta ha sido desactivada. Contacta a soporte.', 403);
        }

        // Revocar tokens anteriores
        $deliveryman->tokens()->delete();

        $token = $deliveryman->createToken('delivery-app')->plainTextToken;

        return $this->success([
            'deliveryman' => $this->formatDeliveryman($deliveryman),
            'token'       => $token,
            'token_type'  => 'Bearer',
        ], 'Inicio de sesión exitoso.');
    }

    /**
     * Cerrar sesión (Repartidor)
     */
    public function logout(): JsonResponse
    {
        auth()->user()->currentAccessToken()->delete();

        return $this->success(null, 'Sesión cerrada exitosamente.');
    }

    /**
     * Perfil del Repartidor
     */
    public function me(): JsonResponse
    {
        return $this->success([
            'deliveryman' => $this->formatDeliveryman(auth()->user()),
        ]);
    }

    /**
     * Actualizar Perfil y Disponibilidad
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $request->validate([
            'name'         => 'sometimes|required|string|max:100',
            'phone'        => 'sometimes|required|string|max:20|unique:deliverymen,phone,' . auth()->id(),
            'is_available' => 'sometimes|required|boolean',
        ]);

        $deliveryman = auth()->user();

        // Actualizamos solo los campos que vengan en el request
        $deliveryman->update($request->only(['name', 'phone', 'is_available']));

        return $this->success([
            'deliveryman' => $this->formatDeliveryman($deliveryman),
        ], 'Perfil actualizado exitosamente.');
    }

    private function formatDeliveryman(Deliveryman $deliveryman): array
    {
        return [
            'id'             => $deliveryman->id,
            'name'           => $deliveryman->name,
            'email'          => $deliveryman->email,
            'phone'          => $deliveryman->phone,
            'vehicle_type'   => $deliveryman->vehicle_type,
            'license_plate'  => $deliveryman->license_plate ?? $deliveryman->vehicle_plate,
            'is_active'      => $deliveryman->is_active,
            'is_available'   => $deliveryman->is_available,
            'branch_id'      => $deliveryman->branch_id,
            'average_rating' => $deliveryman->average_rating,
            'total_reviews'  => $deliveryman->total_reviews,
        ];
    }
}
