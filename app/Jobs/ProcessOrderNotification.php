<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job para enviar notificaciones cuando un pedido cambia de estado.
 *
 * Este job se despacha automáticamente desde OrderObserver cuando
 * el campo 'status' de un pedido cambia.
 *
 * Actualmente las notificaciones son SIMULADAS (logs).
 * En producción, reemplazar con:
 * - Firebase Cloud Messaging (push notifications a la app móvil)
 * - Emails transaccionales (Mailgun, SES, etc.)
 * - SMS (Twilio, etc.)
 */
class ProcessOrderNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Número máximo de reintentos.
     */
    public int $tries = 3;

    /**
     * Segundos de espera entre reintentos.
     */
    public int $backoff = 30;

    /**
     * Crear una nueva instancia del Job.
     */
    public function __construct(
        public readonly Order $order,
        public readonly string $newStatus,
        public readonly string $oldStatus,
    ) {}

    /**
     * Ejecutar el Job.
     */
    public function handle(): void
    {
        // Cargar relaciones necesarias
        $this->order->loadMissing(['user', 'branch', 'deliveryman']);

        $orderId = $this->order->id;
        $userName = $this->order->user?->name ?? 'Cliente';
        $branchName = $this->order->branch?->name ?? 'Sucursal';

        Log::channel('stack')->info("─── Notificación de Pedido #{$orderId} ───");
        Log::channel('stack')->info("Estado: {$this->oldStatus} → {$this->newStatus}");

        // ── Notificar al cliente ────────────────────────────────
        $this->notifyCustomer($orderId, $userName);

        // ── Notificar al restaurante/sucursal ──────────────────
        $this->notifyBranch($orderId, $branchName);

        // ── Notificar al repartidor (si aplica) ────────────────
        if (in_array($this->newStatus, ['assigned', 'on_way'])) {
            $this->notifyDeliveryman($orderId);
        }

        Log::channel('stack')->info("─── Fin Notificación Pedido #{$orderId} ───");
    }

    /**
     * Simular notificación push al cliente.
     *
     * TODO: Implementar con Firebase Cloud Messaging
     */
    private function notifyCustomer(int $orderId, string $userName): void
    {
        $messages = [
            'pending'   => "¡Hola {$userName}! Tu pedido #{$orderId} ha sido recibido. Estamos procesándolo.",
            'confirmed' => "¡{$userName}! Tu pedido #{$orderId} ha sido confirmado por el restaurante. 🎉",
            'preparing' => "Tu pedido #{$orderId} se está preparando. ¡Ya casi! 🍳",
            'assigned'  => "Un repartidor ha sido asignado a tu pedido #{$orderId}. 🏍️",
            'on_way'    => "¡Tu pedido #{$orderId} va en camino! Prepárate para recibirlo. 🚀",
            'delivered' => "Tu pedido #{$orderId} ha sido entregado. ¡Buen provecho! 🎉",
            'cancelled' => "Tu pedido #{$orderId} ha sido cancelado.",
        ];

        $message = $messages[$this->newStatus] ?? "Tu pedido #{$orderId} ha cambiado de estado.";

        Log::channel('stack')->info("[PUSH → Cliente] {$message}");

        // TODO: Reemplazar con implementación real
        // Notification::send($this->order->user, new OrderStatusChanged($this->order, $message));
    }

    /**
     * Simular notificación al restaurante/sucursal.
     *
     * TODO: Implementar con email o panel de notificaciones en Filament
     */
    private function notifyBranch(int $orderId, string $branchName): void
    {
        $messages = [
            'pending'   => "Nuevo pedido #{$orderId} recibido en {$branchName}. Requiere confirmación.",
            'cancelled' => "El pedido #{$orderId} ha sido cancelado por el cliente.",
        ];

        // Solo notificar a la sucursal en ciertos estados
        if (isset($messages[$this->newStatus])) {
            Log::channel('stack')->info("[EMAIL → Sucursal] {$messages[$this->newStatus]}");
        }
    }

    /**
     * Simular notificación al repartidor.
     *
     * TODO: Implementar con push notification o SMS
     */
    private function notifyDeliveryman(int $orderId): void
    {
        $deliverymanName = $this->order->deliveryman?->name ?? 'Repartidor';

        if ($this->newStatus === 'assigned') {
            Log::channel('stack')->info("[PUSH → Repartidor] {$deliverymanName}, tienes un nuevo pedido #{$orderId} asignado.");
        } elseif ($this->newStatus === 'on_way') {
            Log::channel('stack')->info("[PUSH → Repartidor] Pedido #{$orderId} marcado como en camino.");
        }
    }

    /**
     * Manejar un fallo del Job.
     */
    public function failed(?\Throwable $exception): void
    {
        Log::channel('stack')->error(
            "Error al enviar notificación del pedido #{$this->order->id}: " . $exception?->getMessage()
        );
    }
}
