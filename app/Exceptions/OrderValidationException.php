<?php

namespace App\Exceptions;

use Exception;

/**
 * Excepción para errores de validación de lógica de negocio en pedidos.
 *
 * Se lanza cuando un pedido no puede ser procesado por razones de negocio
 * (producto no disponible, variante inválida, dirección no pertenece al usuario, etc.)
 * Diferente de las validaciones de FormRequest (que son de formato/tipo de dato).
 */
class OrderValidationException extends Exception
{
    //
}
