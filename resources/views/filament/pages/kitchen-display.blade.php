{{-- Kitchen Display System (KDS) - Vista de Cocina --}}
<div
    wire:poll.10000ms="loadOrders"
    class="kitchen-container"
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
    {{-- Estilos premium full-screen para la cocina --}}
    <style>
        /* Forzar fondo blanco/claro y resetear márgenes */
        html, body {
            background-color: #f3f4f6 !important;
            margin: 0 !important;
            padding: 0 !important;
            min-height: 100vh;
            color: #111827 !important;
            font-family: ui-sans-serif, system-ui, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji" !important;
        }

        .kitchen-container {
            min-height: 100vh;
            padding: 2rem;
            box-sizing: border-box;
            background-color: #f3f4f6;
        }

        /* Header de la cocina */
        .kitchen-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #ffffff;
            padding: 1.25rem 2rem;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            margin-bottom: 2rem;
            border: 1px solid #e5e7eb;
        }

        .kitchen-title-area {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .kitchen-title {
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: -0.025em;
            color: #111827;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .kitchen-subtitle {
            font-size: 0.95rem;
            color: #4b5563;
            font-weight: 500;
            margin-top: 2px;
        }

        /* Botón de salir elegante */
        .exit-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background-color: #f3f4f6;
            color: #374151;
            padding: 0.6rem 1.2rem;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s ease;
            border: 1px solid #e5e7eb;
        }

        .exit-button:hover {
            background-color: #111827;
            color: #ffffff;
            border-color: #111827;
            transform: translateY(-1px);
        }

        .exit-button:active {
            transform: translateY(0);
        }

        /* Stats & Live status */
        .header-controls {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .live-badge {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: #ecfdf5;
            border: 1.5px solid #a7f3d0;
            padding: 0.5rem 1rem;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 800;
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
            font-size: 1.05rem;
            font-weight: 800;
            padding: 0.4rem 1rem;
            border-radius: 999px;
            box-shadow: 0 4px 6px -1px rgba(17, 24, 39, 0.2);
        }

        /* Grid */
        .kitchen-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
            gap: 2rem;
        }

        /* Tarjetas */
        .order-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
            position: relative;
            user-select: none;
            border: 1px solid #e5e7eb;
        }

        .order-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.08), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .order-card:active {
            transform: scale(0.98);
        }

        /* Barra de color de estado */
        .status-bar {
            height: 16px;
            width: 100%;
            transition: background-color 1s ease;
        }

        .status-green  .status-bar { background: linear-gradient(90deg, #22c55e, #16a34a); }
        .status-yellow .status-bar { background: linear-gradient(90deg, #eab308, #ca8a04); }
        .status-red    .status-bar { background: linear-gradient(90deg, #ef4444, #b91c1c); }

        .status-red .status-bar {
            animation: pulse-red 1.2s ease-in-out infinite;
        }

        @keyframes pulse-red {
            0%, 100% { opacity: 1; }
            50%       { opacity: 0.6; }
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Card Header */
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.25rem;
        }

        .order-number {
            font-size: 2.25rem;
            font-weight: 900;
            color: #111827;
            line-height: 1;
            letter-spacing: -0.03em;
        }

        .order-number span {
            font-size: 1rem;
            font-weight: 600;
            color: #6b7280;
            display: block;
            margin-bottom: 4px;
            letter-spacing: 0.025em;
        }

        .timer-badge {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }

        .timer-text {
            font-size: 1.85rem;
            font-weight: 900;
            font-variant-numeric: tabular-nums;
            line-height: 1;
            letter-spacing: -0.02em;
        }

        .status-green  .timer-text { color: #16a34a; }
        .status-yellow .timer-text { color: #ca8a04; }
        .status-red    .timer-text { color: #b91c1c; }

        .timer-label {
            font-size: 0.72rem;
            font-weight: 700;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-top: 2px;
        }

        .status-chip {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 1.25rem;
            border: 1px solid transparent;
        }

        .chip-confirmed  { background: #dbeafe; color: #1d4ed8; border-color: #bfdbfe; }
        .chip-preparing  { background: #fef3c7; color: #92400e; border-color: #fde68a; }

        .divider {
            height: 1px;
            background: #e5e7eb;
            margin: 1rem 0;
        }

        /* Items */
        .items-title {
            font-size: 0.8rem;
            font-weight: 800;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 0.75rem;
        }

        .item-row {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 0.6rem 0;
            border-bottom: 1px dashed #e5e7eb;
        }

        .item-row:last-child {
            border-bottom: none;
        }

        .item-qty {
            min-width: 2.25rem;
            height: 2.25rem;
            background: #111827;
            color: #ffffff;
            font-size: 1.15rem;
            font-weight: 900;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 4px 6px -1px rgba(17, 24, 39, 0.1);
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-size: 1.15rem;
            font-weight: 800;
            color: #111827;
            line-height: 1.3;
        }

        .item-variant {
            font-size: 0.85rem;
            color: #4b5563;
            font-weight: 600;
            margin-top: 2px;
        }

        .item-extras {
            margin-top: 6px;
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
        }

        .item-extra-tag {
            display: inline-block;
            background: #f3f4f6;
            color: #374151;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }

        .dblclick-hint {
            margin-top: 1.25rem;
            padding: 0.6rem;
            background: #f9fafb;
            border: 1.5px dashed #cbd5e1;
            border-radius: 10px;
            text-align: center;
            font-size: 0.78rem;
            font-weight: 700;
            color: #6b7280;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 8rem 2rem;
            background: #ffffff;
            border-radius: 20px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .empty-icon {
            font-size: 5rem;
            display: block;
            margin-bottom: 1.5rem;
        }

        .empty-title {
            font-size: 1.75rem;
            font-weight: 800;
            color: #111827;
            margin-bottom: 0.5rem;
        }

        .empty-sub {
            font-size: 1.1rem;
            color: #6b7280;
            font-weight: 500;
        }
    </style>

    {{-- Header de la cocina --}}
    <div class="kitchen-header">
        <div class="kitchen-title-area">
            <div>
                <h1 class="kitchen-title">🍳 Pantalla de Cocina</h1>
                <div class="kitchen-subtitle">Monitoreo de pedidos en tiempo real</div>
            </div>
        </div>
        
        <div class="header-controls">
            <span class="order-count-badge">
                {{ $orders->count() }} {{ $orders->count() === 1 ? 'pedido' : 'pedidos' }}
            </span>
            <span class="live-badge">
                <span class="live-dot"></span>
                EN VIVO
            </span>
            <a href="/admin" class="exit-button">
                <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                Volver al Panel
            </a>
        </div>
    </div>

    @if($orders->isEmpty())
        {{-- Estado vacío --}}
        <div class="empty-state">
            <span class="empty-icon">🎉</span>
            <div class="empty-title">¡Todo listo y despachado!</div>
            <div class="empty-sub">No hay pedidos pendientes para preparar en cocina.</div>
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
                            <span class="status-chip chip-confirmed">⏳ Confirmado</span>
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
                                        <div class="item-variant" style="color:#b45309; background-color:#fffbeb; padding: 4px 8px; border-radius: 6px; margin-top: 4px; border: 1px solid #fde68a;">
                                            📝 <strong>Instrucciones:</strong> {{ $item->special_instructions }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach

                        {{-- Hint de interacción --}}
                        <div class="dblclick-hint">
                            👆 Doble click en esta tarjeta para marcar como listo
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
