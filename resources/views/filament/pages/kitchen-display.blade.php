{{-- Kitchen Display System (KDS) - Vista de Cocina --}}
<x-filament-panels::page
    wire:poll.10000ms="loadOrders"
    @class(['fi-page'])
    x-data="{
        orders: @js($orders->map(fn($o) => ['id' => $o->id, 'start_timestamp' => $o->start_timestamp])->values()),
        PREP_TIME_SECS: 20 * 60,

        getElapsed(startTs) {
            if (!startTs) return 0;
            return Math.floor(Date.now() / 1000) - startTs;
        },

        getColorClass(startTs) {
            const elapsed = this.getElapsed(startTs);
            const ratio = elapsed / this.PREP_TIME_SECS;
            if (ratio < 0.6) return 'status-green';
            if (ratio < 1.0) return 'status-yellow';
            return 'status-red';
        },

        getTimerText(startTs) {
            const elapsed = this.getElapsed(startTs);
            const mins = Math.floor(elapsed / 60);
            const secs = elapsed % 60;
            return String(mins).padStart(2,'0') + ':' + String(secs).padStart(2,'0');
        },

        init() {
            setInterval(() => {
                this.$nextTick(() => {});
            }, 1000);
        }
    }"
    x-init="init()"
>
    {{-- Estilos personalizados para la pantalla de cocina --}}
    <style>
        /* Forzar fondo blanco y colores oscuros para toda la vista */
        .fi-page {
            background-color: #f8f9fa !important;
        }

        .kitchen-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
            gap: 1.5rem;
            padding: 1.5rem 0;
        }

        .order-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.10);
            overflow: hidden;
            cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
            position: relative;
            user-select: none;
        }

        .order-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.16);
        }

        .order-card:active {
            transform: scale(0.98);
        }

        /* Barra de color de estado (arriba de la tarjeta) */
        .status-bar {
            height: 14px;
            width: 100%;
            transition: background-color 1s ease;
        }

        .status-green  .status-bar { background: linear-gradient(90deg, #22c55e, #16a34a); }
        .status-yellow .status-bar { background: linear-gradient(90deg, #eab308, #ca8a04); }
        .status-red    .status-bar { background: linear-gradient(90deg, #ef4444, #b91c1c); }

        /* Pulso para los atrasados */
        .status-red .status-bar {
            animation: pulse-red 1.2s ease-in-out infinite;
        }

        @keyframes pulse-red {
            0%, 100% { opacity: 1; }
            50%       { opacity: 0.6; }
        }

        .card-body {
            padding: 1.25rem 1.5rem 1.5rem;
        }

        /* Header de la tarjeta */
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .order-number {
            font-size: 2rem;
            font-weight: 900;
            color: #111827;
            line-height: 1;
        }

        .order-number span {
            font-size: 1rem;
            font-weight: 500;
            color: #6b7280;
            display: block;
            margin-bottom: 2px;
        }

        /* Timer */
        .timer-badge {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }

        .timer-text {
            font-size: 1.6rem;
            font-weight: 800;
            font-variant-numeric: tabular-nums;
            line-height: 1;
        }

        .status-green  .timer-text { color: #16a34a; }
        .status-yellow .timer-text { color: #ca8a04; }
        .status-red    .timer-text { color: #b91c1c; }

        .timer-label {
            font-size: 0.7rem;
            font-weight: 600;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* Estado del pedido */
        .status-chip {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 1rem;
        }

        .chip-confirmed  { background: #dbeafe; color: #1d4ed8; }
        .chip-preparing  { background: #fef3c7; color: #92400e; }

        /* Divisor */
        .divider {
            height: 1px;
            background: #e5e7eb;
            margin: 0.75rem 0;
        }

        /* Lista de items */
        .items-title {
            font-size: 0.75rem;
            font-weight: 700;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            margin-bottom: 0.6rem;
        }

        .item-row {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.5rem 0;
            border-bottom: 1px dashed #f3f4f6;
        }

        .item-row:last-child {
            border-bottom: none;
        }

        .item-qty {
            min-width: 2rem;
            height: 2rem;
            background: #111827;
            color: #ffffff;
            font-size: 1rem;
            font-weight: 800;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-size: 1.05rem;
            font-weight: 700;
            color: #1f2937;
            line-height: 1.2;
        }

        .item-variant {
            font-size: 0.8rem;
            color: #6b7280;
            font-weight: 500;
            margin-top: 1px;
        }

        .item-extras {
            margin-top: 4px;
        }

        .item-extra-tag {
            display: inline-block;
            background: #f3f4f6;
            color: #374151;
            font-size: 0.7rem;
            font-weight: 600;
            padding: 1px 7px;
            border-radius: 999px;
            margin: 2px 2px 0 0;
        }

        /* Hint de doble click */
        .dblclick-hint {
            margin-top: 1rem;
            padding: 0.5rem;
            background: #f9fafb;
            border: 1.5px dashed #d1d5db;
            border-radius: 8px;
            text-align: center;
            font-size: 0.72rem;
            font-weight: 600;
            color: #9ca3af;
        }

        /* Estado vacío */
        .empty-state {
            text-align: center;
            padding: 5rem 2rem;
        }

        .empty-icon {
            font-size: 4rem;
            display: block;
            margin-bottom: 1rem;
        }

        .empty-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .empty-sub {
            font-size: 1rem;
            color: #9ca3af;
        }

        /* Header de la página */
        .kitchen-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.5rem 0 1rem;
            border-bottom: 2px solid #e5e7eb;
            margin-bottom: 0.5rem;
        }

        .kitchen-title {
            font-size: 1.75rem;
            font-weight: 900;
            color: #111827;
        }

        .kitchen-subtitle {
            font-size: 0.9rem;
            color: #6b7280;
            font-weight: 500;
        }

        .live-badge {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: #ecfdf5;
            border: 1.5px solid #a7f3d0;
            padding: 0.4rem 0.9rem;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 700;
            color: #065f46;
        }

        .live-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #10b981;
            animation: blink 1.4s ease-in-out infinite;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50%       { opacity: 0; }
        }

        .order-count-badge {
            background: #111827;
            color: white;
            font-size: 1rem;
            font-weight: 800;
            padding: 0.3rem 0.8rem;
            border-radius: 999px;
        }
    </style>

    {{-- Header de la página --}}
    <div class="kitchen-header">
        <div>
            <div class="kitchen-title">🍳 Cocina</div>
            <div class="kitchen-subtitle">
                Pedidos activos — Doble click para marcar como listo
            </div>
        </div>
        <div style="display:flex; align-items:center; gap:0.75rem;">
            <span class="order-count-badge">
                {{ $orders->count() }} {{ $orders->count() === 1 ? 'pedido' : 'pedidos' }}
            </span>
            <span class="live-badge">
                <span class="live-dot"></span>
                EN VIVO
            </span>
        </div>
    </div>

    @if($orders->isEmpty())
        {{-- Estado vacío --}}
        <div class="empty-state">
            <span class="empty-icon">✅</span>
            <div class="empty-title">¡Todo al día!</div>
            <div class="empty-sub">No hay pedidos pendientes en cocina en este momento.</div>
        </div>
    @else
        {{-- Grid de pedidos --}}
        <div class="kitchen-grid">
            @foreach($orders as $order)
                @php
                    $startTs = $order->start_timestamp;
                @endphp
                <div
                    class="order-card"
                    x-data="{
                        startTs: {{ $startTs ?? 'null' }},
                        clickCount: 0,
                        clickTimer: null,
                        handleClick() {
                            this.clickCount++;
                            if (this.clickCount === 1) {
                                this.clickTimer = setTimeout(() => { this.clickCount = 0; }, 400);
                            } else if (this.clickCount >= 2) {
                                clearTimeout(this.clickTimer);
                                this.clickCount = 0;
                                $wire.markAsReady({{ $order->id }});
                            }
                        }
                    }"
                    :class="getColorClass(startTs)"
                    @click="handleClick()"
                >
                    {{-- Barra de color de tiempo --}}
                    <div class="status-bar"></div>

                    <div class="card-body">
                        {{-- Header de la tarjeta --}}
                        <div class="card-header">
                            <div class="order-number">
                                <span>Pedido</span>
                                #{{ $order->id }}
                            </div>

                            {{-- Timer dinámico --}}
                            <div class="timer-badge">
                                <div class="timer-text" x-text="getTimerText(startTs)">00:00</div>
                                <div class="timer-label">tiempo</div>
                            </div>
                        </div>

                        {{-- Estado --}}
                        @if($order->status === 'confirmed')
                            <span class="status-chip chip-confirmed">✅ Confirmado</span>
                        @elseif($order->status === 'preparing')
                            <span class="status-chip chip-preparing">🔥 En Preparación</span>
                        @endif

                        <div class="divider"></div>

                        {{-- Platillos --}}
                        <div class="items-title">Platillos</div>

                        @foreach($order->items as $item)
                            <div class="item-row">
                                <div class="item-qty">{{ $item->quantity }}</div>
                                <div class="item-details">
                                    <div class="item-name">{{ $item->product?->name ?? 'Producto' }}</div>

                                    @if($item->variant)
                                        <div class="item-variant">
                                            📌 {{ $item->variant->name }}
                                        </div>
                                    @endif

                                    @if($item->extras->isNotEmpty())
                                        <div class="item-extras">
                                            @foreach($item->extras as $extra)
                                                <span class="item-extra-tag">+ {{ $extra->extra?->name ?? 'Extra' }}</span>
                                            @endforeach
                                        </div>
                                    @endif

                                    @if($item->special_instructions)
                                        <div class="item-variant" style="color:#b45309;">
                                            📝 {{ $item->special_instructions }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach

                        {{-- Hint de interacción --}}
                        <div class="dblclick-hint">
                            👆 Doble click para marcar como listo para enviar
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</x-filament-panels::page>
