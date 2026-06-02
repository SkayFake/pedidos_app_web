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
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use App\Mail\PasswordResetMail;
use App\Mail\EmailVerificationMail;

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
        $otp = sprintf("%06d", mt_rand(1, 999999));
        
        $registrationData = [
            'name'             => $request->name,
            'email'            => $request->email,
            'phone'            => $request->phone,
            'password'         => Hash::make($request->password),
            'verification_otp' => $otp,
        ];

        // Almacenar en caché temporal por 15 minutos en lugar de crear la cuenta
        \Illuminate\Support\Facades\Cache::put('registration:' . $request->email, $registrationData, now()->addMinutes(15));

        try {
            Mail::to($request->email)->send(new EmailVerificationMail($otp));
        } catch (\Exception $e) {
            \Log::error("Failed to send verification email to {$request->email}: " . $e->getMessage());
        }

        return $this->success([
            'email' => $request->email
        ], 'Registro iniciado. Revisa tu correo para el código de validación.', 200);
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
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        $user = auth()->user();
        $data = [
            'name'  => $request->name,
            'phone' => $request->phone,
        ];

        if ($request->hasFile('image')) {
            if ($user->image) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->image);
            }
            $data['image'] = $request->file('image')->store('profile', 'public');
        }

        $user->update($data);

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
     * Verificar Correo Electrónico
     */
    public function verifyEmail(\Illuminate\Http\Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'otp'   => 'required|string|size:6',
        ]);

        $cacheKey = 'registration:' . $request->email;
        $registrationData = \Illuminate\Support\Facades\Cache::get($cacheKey);

        if (!$registrationData) {
            return $this->error('El código ha expirado o el correo no es válido. Regístrate de nuevo.', 422);
        }

        if ($registrationData['verification_otp'] !== $request->otp) {
            return $this->error('El código de verificación es incorrecto.', 422);
        }

        // Crear el usuario finalmente en la base de datos
        $user = User::create([
            'name'              => $registrationData['name'],
            'email'             => $registrationData['email'],
            'phone'             => $registrationData['phone'],
            'password'          => $registrationData['password'],
            'email_verified_at' => now(),
        ]);

        // Limpiar el caché
        \Illuminate\Support\Facades\Cache::forget($cacheKey);

        $token = $user->createToken('mobile-app')->plainTextToken;

        return $this->success([
            'user'       => $this->formatUser($user),
            'token'      => $token,
            'token_type' => 'Bearer',
        ], 'Correo verificado y cuenta creada exitosamente.', 201);
    }

    /**
     * Reenviar código de verificación de correo
     */
    public function resendVerificationEmail(\Illuminate\Http\Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $cacheKey = 'registration:' . $request->email;
        $registrationData = \Illuminate\Support\Facades\Cache::get($cacheKey);

        if (!$registrationData) {
            return $this->error('El registro ha expirado o el correo no es válido. Por favor, regístrate de nuevo.', 422);
        }

        // Generar nuevo OTP
        $otp = sprintf("%06d", mt_rand(1, 999999));
        $registrationData['verification_otp'] = $otp;

        // Actualizar caché por otros 15 minutos
        \Illuminate\Support\Facades\Cache::put($cacheKey, $registrationData, now()->addMinutes(15));

        try {
            Mail::to($request->email)->send(new \App\Mail\EmailVerificationMail($otp));
        } catch (\Exception $e) {
            \Log::error("Failed to resend verification email to {$request->email}: " . $e->getMessage());
        }

        return $this->success([
            'email' => $request->email
        ], 'Nuevo código de verificación enviado. Revisa tu correo.', 200);
    }

    /**
     * Solicitar recuperación de contraseña (Forgot Password)
     */
    public function forgotPassword(\Illuminate\Http\Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $otp = sprintf("%06d", mt_rand(1, 999999));
        
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            ['token' => $otp, 'created_at' => now()]
        );

        try {
            Mail::to($request->email)->send(new PasswordResetMail($otp));
        } catch (\Exception $e) {
            return $this->error('Error al enviar el correo. Inténtalo más tarde.', 500);
        }

        return $this->success(null, 'Se ha enviado un código de recuperación a tu correo.');
    }

    /**
     * Restablecer contraseña con OTP
     */
    public function resetPassword(\Illuminate\Http\Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email|exists:users,email',
            'otp'      => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $reset = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->otp)
            ->first();

        if (!$reset) {
            return $this->error('El código es inválido o ha expirado.', 422);
        }

        // Validate expiration (15 mins)
        if (\Carbon\Carbon::parse($reset->created_at)->addMinutes(15)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return $this->error('El código de recuperación ha expirado.', 422);
        }

        User::where('email', $request->email)->update([
            'password' => Hash::make($request->password)
        ]);

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return $this->success(null, 'Contraseña restablecida exitosamente. Ahora puedes iniciar sesión.');
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
            'image'                  => $user->image,
            'image_url'              => $user->image_url,
            'profile_photo'          => $user->image_url, // Por compatibilidad con clientes existentes
            'is_active'              => $user->is_active,
            'loyalty_points'         => $user->loyalty_points,
            'total_completed_orders' => $user->total_completed_orders,
        ];
    }
}
