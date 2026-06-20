<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

/**
 * @group Autenticación Google
 *
 * Endpoints para inicio de sesión y registro utilizando el ID Token de Google.
 */
class GoogleAuthController extends Controller
{
    use ApiResponse;

    /**
     * Iniciar sesión o registrarse con Google
     *
     * Valida un ID Token recibido desde Google.
     * Si el usuario ya existe, inicia sesión inmediatamente.
     * Si el usuario no existe, solicita el número de teléfono obligatorio.
     *
     * @unauthenticated
     *
     * @bodyParam id_token string required ID Token de Google.
     * @bodyParam phone string optional Número de teléfono (obligatorio para el registro de nuevos usuarios).
     */
    public function googleLogin(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id_token' => 'required|string',
            'phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return $this->error('Error de validación.', 422, $validator->errors()->toArray());
        }

        $idToken = $request->input('id_token');
        $phone = $request->input('phone');

        // Validar el ID Token con la API de Google
        try {
            $response = Http::get('https://oauth2.googleapis.com/tokeninfo', [
                'id_token' => $idToken,
            ]);

            if (!$response->successful()) {
                return $this->error('El token de Google no es válido o ha expirado.', 401);
            }

            $payload = $response->json();

            if (!isset($payload['email'])) {
                return $this->error('No se pudo obtener el correo de Google.', 400);
            }

            $email = $payload['email'];
            $name = $payload['name'] ?? 'Usuario de Google';
            $picture = $payload['picture'] ?? null;
            $emailVerified = filter_var($payload['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);

            if (!$emailVerified) {
                return $this->error('El correo electrónico de Google no está verificado.', 401);
            }
        } catch (\Exception $e) {
            \Log::error('Error verificando Google Token: ' . $e->getMessage());
            return $this->error('Error de comunicación con los servidores de Google.', 500);
        }

        // Buscar el usuario por email
        $user = User::where('email', $email)->first();

        if ($user) {
            if (!$user->is_active) {
                return $this->error('Tu cuenta ha sido desactivada. Contacta a soporte.', 403);
            }

            // Si el usuario existe pero no tiene foto de perfil y Google nos proporciona una, actualizarla opcionalmente
            if (!$user->profile_photo && $picture) {
                $user->update(['profile_photo' => $picture]);
            }

            // Revocar tokens anteriores y generar nueva sesión
            $user->tokens()->delete();
            $token = $user->createToken('mobile-app', ['customer'])->plainTextToken;

            return $this->success([
                'user' => $this->formatUser($user),
                'token' => $token,
                'token_type' => 'Bearer',
                'requires_phone' => false,
            ], 'Inicio de sesión con Google exitoso.');
        }

        // Si el usuario NO existe, requerimos el número de teléfono
        if (empty($phone)) {
            return $this->success([
                'requires_phone' => true,
                'email' => $email,
                'name' => $name,
                'picture' => $picture,
            ], 'El usuario no está registrado. Se requiere número de teléfono.', 200);
        }

        // Si el número de teléfono fue provisto, procedemos a validar que sea único
        $phoneValidator = Validator::make($request->all(), [
            'phone' => 'required|string|max:20|unique:users,phone',
        ], [
            'phone.unique' => 'Este número de teléfono ya está registrado por otro usuario.',
        ]);

        if ($phoneValidator->fails()) {
            return $this->error('Error de validación del teléfono.', 422, $phoneValidator->errors()->toArray());
        }

        // Crear el nuevo usuario usando forceCreate para eludir $guarded
        $user = User::forceCreate([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password' => Hash::make(Str::random(16)),
            'email_verified_at' => now(),
            'profile_photo' => $picture,
            'is_active' => true,
        ]);

        $token = $user->createToken('mobile-app', ['customer'])->plainTextToken;

        return $this->success([
            'user' => $this->formatUser($user),
            'token' => $token,
            'token_type' => 'Bearer',
            'requires_phone' => false,
        ], 'Registro e inicio de sesión con Google exitoso.', 201);
    }

    /**
     * Formatear datos del usuario para la respuesta.
     */
    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'profile_photo' => $user->profile_photo,
            'is_active' => (bool)$user->is_active,
            'loyalty_points' => (int)$user->loyalty_points,
            'total_completed_orders' => (int)$user->total_completed_orders,
        ];
    }
}
