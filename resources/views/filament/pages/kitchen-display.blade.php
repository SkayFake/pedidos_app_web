{{-- Kitchen Display System (KDS) - Vista de Cocina --}}
<div
    wire:poll.5000ms="loadOrders"
    class="kitchen-container"
    x-data="{
        now: Math.floor(Date.now() / 1000),
        PREP_TIME_SECS: 20 * 60,

        getElapsed(startTs) {
            if (!startTs) return 0;
            return this.now - startTs;
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
                this.now = Math.floor(Date.now() / 1000);
            }, 1000);
        }
    }"
    x-init="init()"
>
    {{-- Estilos premium full-screen para la cocina --}}
    <style>
        /* Forzar fondo blanco/claro y resetear márgenes */
        html, body {
            margin: 0 !important;
            padding: 0 !important;
            min-height: 100vh;
            font-family: 'Inter', ui-sans-serif, system-ui, sans-serif !important;
        }

        .kitchen-container {
            min-height: 100%;
            padding: 1rem;
            box-sizing: border-box;
            background-color: var(--fi-bg, transparent);
            color: inherit;
        }

        @media (min-width: 640px) {
            .kitchen-container { padding: 1.5rem; }
        }

        @media (min-width: 1024px) {
            .kitchen-container { padding: 2rem; }
        }

        /* Header de la cocina */
        .kitchen-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            background: #ffffff;
            padding: 1rem 1.25rem;
            border-radius: 16px;
            box-shadow: var(--shadow-soft, 0 4px 6px -1px rgba(0, 0, 0, 0.05));
            margin-bottom: 1.5rem;
            border: 1px solid rgba(0, 119, 182, 0.08);
        }
        
        :is(.dark) .kitchen-header {
            background: rgba(15, 23, 42, 0.6);
            border-color: rgba(0, 180, 216, 0.08);
        }

        @media (min-width: 768px) {
            .kitchen-header {
                padding: 1.25rem 2rem;
                margin-bottom: 2rem;
                flex-wrap: nowrap;
            }
        }

        .kitchen-title-area {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex: 1;
        }

        .kitchen-title {
            font-size: 1.4rem;
            font-weight: 800;
            letter-spacing: -0.025em;
            color: inherit;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        @media (min-width: 768px) {
            .kitchen-title { font-size: 2rem; }
        }

        .kitchen-subtitle {
            font-size: 0.95rem;
            color: #64748b;
            font-weight: 500;
            margin-top: 2px;
        }
        :is(.dark) .kitchen-subtitle { color: #94a3b8; }

        /* Botón de salir elegante */
        .exit-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background-color: #f1f5f9;
            color: #334155;
            padding: 0.6rem 1.2rem;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s ease;
            border: 1px solid #e2e8f0;
        }
        :is(.dark) .exit-button {
            background-color: rgba(255,255,255,0.05);
            color: #e2e8f0;
            border-color: rgba(255,255,255,0.1);
        }

        .exit-button:hover {
            background-color: var(--ocean-primary, #0077B6);
            color: #ffffff;
            border-color: var(--ocean-primary, #0077B6);
            transform: translateY(-1px);
        }

        .exit-button:active {
            transform: translateY(0);
        }

        /* Stats & Live status */
        .header-controls {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .live-badge {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(16,185,129,0.1);
            border: 1px solid rgba(16,185,129,0.2);
            padding: 0.5rem 1rem;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 800;
            color: #059669;
        }
        :is(.dark) .live-badge { color: #34d399; }

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
            background: #f1f5f9;
            color: #334155;
            font-size: 1.05rem;
            font-weight: 800;
            padding: 0.4rem 1rem;
            border-radius: 999px;
            border: 1px solid #e2e8f0;
        }
        :is(.dark) .order-count-badge {
            background: rgba(255,255,255,0.05);
            color: #e2e8f0;
            border-color: rgba(255,255,255,0.1);
        }

        /* Grid */
        .kitchen-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        @media (min-width: 480px) {
            .kitchen-grid { grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.25rem; }
        }

        @media (min-width: 1024px) {
            .kitchen-grid { grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap: 2rem; }
        }

        /* Tarjetas */
        .order-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: var(--shadow-soft, 0 4px 6px -1px rgba(0,0,0,0.05));
            overflow: hidden;
            cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
            position: relative;
            user-select: none;
            border: 1px solid rgba(0, 119, 182, 0.08);
        }
        
        :is(.dark) .order-card {
            background: rgba(15, 23, 42, 0.6);
            border-color: rgba(0, 180, 216, 0.08);
        }

        .order-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-medium, 0 10px 15px -3px rgba(0,0,0,0.1));
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
            padding: 1rem;
        }

        @media (min-width: 640px) {
            .card-body { padding: 1.5rem; }
        }

        /* Card Header */
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.25rem;
        }

        .order-number {
            font-size: 1.75rem;
            font-weight: 900;
            color: inherit;
            line-height: 1;
            letter-spacing: -0.03em;
        }

        @media (min-width: 640px) {
            .order-number { font-size: 2.25rem; }
        }

        .order-number span {
            font-size: 1rem;
            font-weight: 600;
            color: #64748b;
            display: block;
            margin-bottom: 4px;
            letter-spacing: 0.025em;
        }
        :is(.dark) .order-number span { color: #94a3b8; }

        .timer-badge {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }

        .timer-text {
            font-size: 1.4rem;
            font-weight: 900;
            font-variant-numeric: tabular-nums;
            line-height: 1;
            letter-spacing: -0.02em;
        }

        @media (min-width: 640px) {
            .timer-text { font-size: 1.85rem; }
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

        .chip-confirmed  { background: rgba(37,99,235,0.12); color: #2563eb; border-color: rgba(37,99,235,0.25); }
        :is(.dark) .chip-confirmed { color: #60a5fa; }
        
        .chip-preparing  { background: rgba(217,119,6,0.12); color: #d97706; border-color: rgba(217,119,6,0.25); }
        :is(.dark) .chip-preparing { color: #fbbf24; }

        .divider {
            height: 1px;
            background: rgba(0,0,0,0.06);
            margin: 1rem 0;
        }
        :is(.dark) .divider { background: rgba(255,255,255,0.08); }

        /* Items */
        .items-title {
            font-size: 0.8rem;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 0.75rem;
        }
        :is(.dark) .items-title { color: #94a3b8; }

        .item-row {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 0.6rem 0;
            border-bottom: 1px dashed rgba(0,0,0,0.08);
        }
        :is(.dark) .item-row { border-bottom-color: rgba(255,255,255,0.1); }

        .item-row:last-child {
            border-bottom: none;
        }

        .item-qty {
            min-width: 2.25rem;
            height: 2.25rem;
            background: #f1f5f9;
            color: #334155;
            font-size: 1.15rem;
            font-weight: 900;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        :is(.dark) .item-qty {
            background: rgba(255,255,255,0.1);
            color: #e2e8f0;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-size: 1.15rem;
            font-weight: 800;
            color: inherit;
            line-height: 1.3;
        }

        .item-variant {
            font-size: 0.85rem;
            color: #64748b;
            font-weight: 600;
            margin-top: 2px;
        }
        :is(.dark) .item-variant { color: #94a3b8; }

        .item-extras {
            margin-top: 6px;
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
        }

        .item-extra-tag {
            display: inline-block;
            background: rgba(0,0,0,0.04);
            color: #475569;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 8px;
            border: 1px solid rgba(0,0,0,0.05);
        }
        :is(.dark) .item-extra-tag {
            background: rgba(255,255,255,0.05);
            color: #cbd5e1;
            border-color: rgba(255,255,255,0.1);
        }

        .dblclick-hint {
            margin-top: 1.25rem;
            padding: 0.6rem;
            background: rgba(0,0,0,0.02);
            border: 1.5px dashed rgba(0,0,0,0.1);
            border-radius: 10px;
            text-align: center;
            font-size: 0.78rem;
            font-weight: 700;
            color: #64748b;
        }
        :is(.dark) .dblclick-hint {
            background: rgba(255,255,255,0.02);
            border-color: rgba(255,255,255,0.1);
            color: #94a3b8;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 8rem 2rem;
            background: #ffffff;
            border-radius: 20px;
            border: 1px dashed rgba(0, 119, 182, 0.2);
            box-shadow: var(--shadow-soft, 0 4px 6px -1px rgba(0, 0, 0, 0.05));
        }
        :is(.dark) .empty-state {
            background: rgba(15, 23, 42, 0.6);
            border-color: rgba(0, 180, 216, 0.08);
        }

        .empty-icon {
            font-size: 5rem;
            display: block;
            margin-bottom: 1.5rem;
        }

        .empty-title {
            font-size: 1.75rem;
            font-weight: 800;
            color: inherit;
            margin-bottom: 0.5rem;
        }

        .empty-sub {
            font-size: 1.1rem;
            color: #64748b;
            font-weight: 500;
        }
        :is(.dark) .empty-sub { color: #94a3b8; }
    </style>

    {{-- Header de la cocina --}}
    <div class="kitchen-header">
        <div class="kitchen-title-area">
            <div>
                <h1 class="kitchen-title">Pantalla de Cocina</h1>
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
            <svg style="width:80px; height:80px; margin: 0 auto 1.5rem; display:block; color:#10b981;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
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
                            <span class="status-chip chip-confirmed">
                                <svg style="width:14px; height:14px; display:inline-block; vertical-align:middle; margin-right:4px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>Confirmado
                            </span>
                        @elseif($order->status === 'preparing')
                            <span class="status-chip chip-preparing">
                                <svg style="width:14px; height:14px; display:inline-block; vertical-align:middle; margin-right:4px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z" />
                                </svg>En Preparación
                            </span>
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
                                            <svg style="width:12px; height:12px; display:inline-block; vertical-align:middle; margin-right:4px; color:#4b5563;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>{{ $item->variant->name }}
                                        </div>
                                    @endif

                                    @if($item->extras->isNotEmpty())
                                        <div class="item-extras">
                                            @foreach($item->extras as $extra)
                                                <span class="item-extra-tag">+ {{ $extra->extra?->name ?? 'Extra' }}</span>
                                            @endforeach
                                        </div>
                                    @endif

                                </div>
                            </div>
                        @endforeach

                        @if($order->notes)
                            <div class="divider"></div>
                            <div class="items-title" style="color: #b45309; margin-bottom: 0.5rem;">
                                <svg style="width:14px; height:14px; display:inline-block; vertical-align:middle; margin-right:4px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>Notas del Cliente
                            </div>
                            <div style="background-color:#fffbeb; padding: 10px; border-radius: 8px; border: 1px solid #fde68a; font-size: 0.9rem; color: #92400e; font-weight: 500;">
                                {{ $order->notes }}
                            </div>
                        @endif

                        {{-- Hint de interacción --}}
                        <div class="dblclick-hint">
                            <svg style="width:14px; height:14px; display:inline-block; vertical-align:middle; margin-right:4px; color:#6b7280;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" />
                            </svg>Doble click en esta tarjeta para marcar como listo
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
