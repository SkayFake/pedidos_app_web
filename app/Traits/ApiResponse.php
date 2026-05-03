<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * Respuesta exitosa estandarizada.
     */
    protected function success(mixed $data = null, string $message = 'OK', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $code);
    }

    /**
     * Respuesta de error estandarizada.
     */
    protected function error(string $message = 'Error', int $code = 400, mixed $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }
}
