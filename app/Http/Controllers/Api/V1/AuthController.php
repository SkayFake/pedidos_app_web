<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * @group Autenticación
 *
 * Endpoints para registro, inicio de sesión y gestión de la sesión
 * de los clientes de la app móvil. Usa Laravel Sanctum con tokens Bearer.
 */
class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Registrar cliente
     *
     * Crea una nueva cuenta de cliente y emite un token de acceso Sanctum.
     * El token debe incluirse en las solicitudes posteriores como `Authorization: Bearer {token}`.
     *
     * @unauthenticated
     *
     * @bodyParam name string required Nombre completo del cliente. Example: Juan Pérez
     * @bodyParam email string required Correo electrónico único. Example: juan@example.com
     * @bodyParam phone string required Número de teléfono único. Example: +503 7890-1234
     * @bodyParam password string required Contraseña (mínimo 8 caracteres). Example: password123
     * @bodyParam password_confirmation string required Confirmación de contraseña. Example: password123
     *
     * @response 201 {
     *   "success": true,
     *   "message": "Registro exitoso.",
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "name": "Juan Pérez",
     *       "email": "juan@example.com",
     *       "phone": "+503 7890-1234",
     *       "profile_photo": null,
     *       "is_active": true,
     *       "loyalty_points": 0,
     *       "total_completed_orders": 0
     *     },
     *     "token": "1|abc123def456...",
     *     "token_type": "Bearer"
     *   }
     * }
     * @response 422 {
     *   "success": false,
     *   "message": "Error de validación.",
     *   "errors": {
     *     "email": ["Este correo electrónico ya está registrado."]
     *   }
     * }
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'phone'    => $request->phone,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('mobile-app')->plainTextToken;

        return $this->success([
            'user'       => $this->formatUser($user),
            'token'      => $token,
            'token_type' => 'Bearer',
        ], 'Registro exitoso.', 201);
    }

    /**
     * Iniciar sesión
     *
     * Autentica al cliente y emite un nuevo token Sanctum.
     * Los tokens anteriores son revocados para mantener una sola sesión activa.
     *
     * @unauthenticated
     *
     * @bodyParam email string required Correo electrónico del cliente. Example: juan@example.com
     * @bodyParam password string required Contraseña del cliente. Example: password123
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Inicio de sesión exitoso.",
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "name": "Juan Pérez",
     *       "email": "juan@example.com",
     *       "phone": "+503 7890-1234",
     *       "profile_photo": null,
     *       "is_active": true,
     *       "loyalty_points": 150,
     *       "total_completed_orders": 12
     *     },
     *     "token": "2|xyz789ghi012...",
     *     "token_type": "Bearer"
     *   }
     * }
     * @response 401 {
     *   "success": false,
     *   "message": "Credenciales incorrectas."
     * }
     * @response 403 {
     *   "success": false,
     *   "message": "Tu cuenta ha sido desactivada. Contacta a soporte."
     * }
     */
    public function login(LoginRequest $request): JsonResponse
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return $this->error('Credenciales incorrectas.', 401);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user->is_active) {
            return $this->error('Tu cuenta ha sido desactivada. Contacta a soporte.', 403);
        }

        // Revocar tokens anteriores para mantener una sola sesión activa
        $user->tokens()->delete();

        $token = $user->createToken('mobile-app')->plainTextToken;

        return $this->success([
            'user'       => $this->formatUser($user),
            'token'      => $token,
            'token_type' => 'Bearer',
        ], 'Inicio de sesión exitoso.');
    }

    /**
     * Cerrar sesión
     *
     * Revoca el token de acceso actual del cliente.
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Sesión cerrada exitosamente.",
     *   "data": null
     * }
     */
    public function logout(): JsonResponse
    {
        auth()->user()->currentAccessToken()->delete();

        return $this->success(null, 'Sesión cerrada exitosamente.');
    }

    /**
     * Perfil del usuario
     *
     * Retorna los datos del cliente autenticado, incluyendo puntos de lealtad
     * y total de pedidos completados.
     *
     * @response 200 {
     *   "success": true,
     *   "message": "OK",
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "name": "Juan Pérez",
     *       "email": "juan@example.com",
     *       "phone": "+503 7890-1234",
     *       "profile_photo": null,
     *       "is_active": true,
     *       "loyalty_points": 150,
     *       "total_completed_orders": 12
     *     }
     *   }
     * }
     */
    public function me(): JsonResponse
    {
        return $this->success([
            'user' => $this->formatUser(auth()->user()),
        ]);
    }

    /**
     * Actualizar perfil
     *
     * @bodyParam name string required Nombre del cliente.
     * @bodyParam phone string required Teléfono del cliente.
     */
    public function updateProfile(\Illuminate\Http\Request $request): JsonResponse
    {
        $request->validate([
            'name'  => 'required|string|max:100',
            'phone' => 'required|string|max:20|unique:users,phone,' . auth()->id(),
        ]);

        $user = auth()->user();
        $user->update([
            'name'  => $request->name,
            'phone' => $request->phone,
        ]);

        return $this->success([
            'user' => $this->formatUser($user),
        ], 'Perfil actualizado exitosamente.');
    }

    /**
     * Cambiar contraseña
     *
     * @bodyParam current_password string required Contraseña actual.
     * @bodyParam new_password string required Nueva contraseña (min 8).
     */
    public function changePassword(\Illuminate\Http\Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:8',
        ]);

        $user = auth()->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return $this->error('La contraseña actual es incorrecta.', 422);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return $this->success(null, 'Contraseña actualizada exitosamente.');
    }

    /**
     * Formatear datos del usuario para la respuesta.
     */
    private function formatUser(User $user): array
    {
        return [
            'id'                     => $user->id,
            'name'                   => $user->name,
            'email'                  => $user->email,
            'phone'                  => $user->phone,
            'profile_photo'          => $user->profile_photo,
            'is_active'              => $user->is_active,
            'loyalty_points'         => $user->loyalty_points,
            'total_completed_orders' => $user->total_completed_orders,
        ];
    }
}
