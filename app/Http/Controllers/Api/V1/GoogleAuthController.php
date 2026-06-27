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
     * Valida un ID Token o Access Token recibido desde Google.
     * Si el usuario ya existe, inicia sesión inmediatamente.
     * Si el usuario no existe, solicita el número de teléfono obligatorio.
     *
     * @unauthenticated
     *
     * @bodyParam id_token string optional ID Token de Google (preferido).
     * @bodyParam access_token string optional Access Token de Google (fallback para web).
     * @bodyParam phone string optional Número de teléfono (obligatorio para el registro de nuevos usuarios).
     */
    public function googleLogin(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id_token' => 'nullable|string',
            'access_token' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return $this->error('Error de validación.', 422, $validator->errors()->toArray());
        }

        $idToken = $request->input('id_token');
        $accessToken = $request->input('access_token');
        $phone = $request->input('phone');

        if (empty($idToken) && empty($accessToken)) {
            return $this->error('Se requiere id_token o access_token.', 422);
        }

        // Intentar validar con id_token primero, luego con access_token
        $email = null;
        $name = null;
        $picture = null;
        $emailVerified = false;

        if (!empty($idToken)) {
            try {
                $response = Http::get('https://oauth2.googleapis.com/tokeninfo', [
                    'id_token' => $idToken,
                ]);

                if ($response->successful()) {
                    $payload = $response->json();
                    $email = $payload['email'] ?? null;
                    $name = $payload['name'] ?? 'Usuario de Google';
                    $picture = $payload['picture'] ?? null;
                    $emailVerified = filter_var($payload['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);
                }
            } catch (\Exception $e) {
                \Log::warning('Error verificando Google ID Token: ' . $e->getMessage());
            }
        }

        // Fallback: usar access_token con Google UserInfo API
        if (empty($email) && !empty($accessToken)) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                ])->get('https://www.googleapis.com/oauth2/v3/userinfo');

                if ($response->successful()) {
                    $payload = $response->json();
                    $email = $payload['email'] ?? null;
                    $name = $payload['name'] ?? 'Usuario de Google';
                    $picture = $payload['picture'] ?? null;
                    $emailVerified = filter_var($payload['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);
                } else {
                    \Log::error('Google UserInfo API error: ' . $response->body());
                    return $this->error('El token de Google no es válido o ha expirado.', 401);
                }
            } catch (\Exception $e) {
                \Log::error('Error verificando Google Access Token: ' . $e->getMessage());
                return $this->error('Error de comunicación con los servidores de Google.', 500);
            }
        }

        if (empty($email)) {
            return $this->error('No se pudo obtener el correo de Google.', 400);
        }

        if (!$emailVerified) {
            return $this->error('El correo electrónico de Google no está verificado.', 401);
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

            // Mantener solo los 4 tokens más recientes; eliminar el más antiguo si hay 5 o más.
            $maxTokens = 5;
            $tokenCount = $user->tokens()->count();
            if ($tokenCount >= $maxTokens) {
                $user->tokens()->oldest('created_at')->limit($tokenCount - $maxTokens + 1)->delete();
            }
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
        try {
            $user = User::forceCreate([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'password' => Hash::make(Str::random(16)),
                'email_verified_at' => now(),
                'profile_photo' => $picture,
                'is_active' => true,
            ]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            // El email ya existe (p.ej. por un reintento o condición de carrera).
            // Buscamos al usuario existente y lo autenticamos directamente.
            \Log::warning("Google register fallback to login for email: {$email}. Reason: " . $e->getMessage());
            $user = User::where('email', $email)->first();
            if (!$user) {
                return $this->error('No se pudo crear ni encontrar la cuenta. Intenta de nuevo.', 500);
            }
            if (!$user->is_active) {
                return $this->error('Tu cuenta ha sido desactivada. Contacta a soporte.', 403);
            }
        }

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
