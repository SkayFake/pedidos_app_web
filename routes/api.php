<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CustomerAddressController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ZoneController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — AXStore v1
|--------------------------------------------------------------------------
|
| Todas las rutas tienen el prefijo /api/v1 (configurado en bootstrap/app.php).
| La autenticación usa Laravel Sanctum con tokens Bearer.
|
*/

// ── Rutas Públicas (Autenticación) ─────────────────────────────────────
Route::prefix('auth')->middleware('throttle:auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// ── Rutas Públicas (Catálogo y Configuración) ───────────────────────────
Route::get('/zones', [ZoneController::class, 'index']);

// ── Rutas Protegidas (requieren token Sanctum) ─────────────────────────
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {

    // Auth (sesión y perfil)
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::put('/update-profile', [AuthController::class, 'updateProfile']);
        Route::put('/change-password', [AuthController::class, 'changePassword']);
    });

    // Productos (solo lectura)
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{product}', [ProductController::class, 'show']);

    // Categorías
    Route::get('/categories', [CategoryController::class, 'index']);

    // Pedidos
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);

    // Direcciones del cliente
    Route::apiResource('addresses', CustomerAddressController::class)
        ->except(['show']);
});
