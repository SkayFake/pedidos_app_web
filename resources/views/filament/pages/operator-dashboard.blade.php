{{-- ╔══════════════════════════════════════════════════════════╗ --}}
{{-- ║   PANEL DE OPERADOR — Delivery App                      ║ --}}
{{-- ╚══════════════════════════════════════════════════════════╝ --}}
<div
    wire:poll.5000ms="pollOrders"
    x-data="{
        now: Math.floor(Date.now() / 1000),
        alertVisible: false,
        cancelModalOpen: false,
        cancelOrderId: null,
        cancelReason: '',
        confirmModalOpen: false,
        confirmOrderId: null,
        confirmOrderName: '',
        confirmOrderTotal: '',
        confirmOrderItems: [],
        audioCtx: null,

        init() {
            setInterval(() => { this.now = Math.floor(Date.now() / 1000); }, 1000);
            this.$wire.on('new-order-arrived', () => {
                this.alertVisible = true;
                this.playAlert();
            });
        },

        getAgeSeconds(createdAtIso) {
            if (!createdAtIso) return 0;
            return Math.floor(Date.now() / 1000) - Math.floor(new Date(createdAtIso).getTime() / 1000);
        },

        formatAge(secs) {
            const m = Math.floor(secs / 60);
            const s = secs % 60;
            return String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
        },

        urgency(secs) {
            if (secs < 120)  return 'new';
            if (secs < 300)  return 'normal';
            if (secs < 600)  return 'warning';
            return 'urgent';
        },

        playAlert() {
            try {
                if (!this.audioCtx) this.audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                const ctx = this.audioCtx;
                const notes = [523.25, 659.25, 783.99, 1046.50];
                notes.forEach((freq, i) => {
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.frequency.value = freq;
                    osc.type = 'sine';
                    gain.gain.setValueAtTime(0, ctx.currentTime + i * 0.12);
                    gain.gain.linearRampToValueAtTime(0.4, ctx.currentTime + i * 0.12 + 0.05);
                    gain.gain.linearRampToValueAtTime(0, ctx.currentTime + i * 0.12 + 0.2);
                    osc.start(ctx.currentTime + i * 0.12);
                    osc.stop(ctx.currentTime + i * 0.12 + 0.25);
                });
            } catch(e) {}
        },

        openCancelModal(id) {
            this.cancelOrderId = id;
            this.cancelReason  = '';
            this.cancelModalOpen = true;
        },

        submitCancel() {
            if (!this.cancelReason.trim()) return;
            this.$wire.cancelOrder(this.cancelOrderId, this.cancelReason);
            this.cancelModalOpen = false;
        },

        openConfirmModal(id, name, total, items) {
            this.confirmOrderId    = id;
            this.confirmOrderName  = name;
            this.confirmOrderTotal = total;
            this.confirmOrderItems = items;
            this.confirmModalOpen  = true;
        },

        submitConfirm() {
            this.$wire.confirmOrder(this.confirmOrderId);
            this.confirmModalOpen = false;
        }
    }"
    x-init="init()"
    class="op-root"
>

{{-- ═══════════════════════ ESTILOS ══════════════════════════ --}}
<style>
    /* ── Reset + Dark base ───────────────────────────────────── */
    .op-root *, .op-root *::before, .op-root *::after { box-sizing: border-box; }

    .op-root {
        min-height: 100vh;
        background: linear-gradient(135deg, #0f0c29 0%, #302b63 50%, #24243e 100%);
        padding: 1.5rem;
        font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
        color: #f0f4ff;
        position: relative;
    }
    @media (min-width: 640px) {
        .op-root { padding: 1.5rem; }
    }

    /* ── Header ─────────────────────────────────────────────── */
    .op-header {
        display: flex; align-items: center; justify-content: space-between;
        flex-wrap: wrap; gap: 1rem;
        background: rgba(255,255,255,0.05);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 16px;
        padding: 1rem 1.25rem;
        margin-bottom: 1.25rem;
        box-shadow: 0 8px 32px rgba(0,0,0,0.3);
    }

    @media (min-width: 768px) {
        .op-header {
            border-radius: 20px;
            padding: 1.25rem 2rem;
            margin-bottom: 1.75rem;
        }
    }

    .op-header-left { display: flex; align-items: center; gap: 0.75rem; flex: 1; min-width: 0; }
    .op-logo {
        width: 40px; height: 40px; flex-shrink: 0;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.25rem;
        box-shadow: 0 4px 15px rgba(99,102,241,0.4);
    }

    @media (min-width: 640px) {
        .op-logo { width: 48px; height: 48px; border-radius: 14px; font-size: 1.5rem; }
    }
    .op-title { font-size: 1.2rem; font-weight: 900; letter-spacing: -0.03em; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .op-sub   { font-size: 0.75rem; color: rgba(255,255,255,0.5); font-weight: 500; display: none; }

    @media (min-width: 640px) {
        .op-title { font-size: 1.6rem; }
        .op-sub   { display: block; }
    }

    .op-header-right { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }

    .op-live {
        display: flex; align-items: center; gap: 0.5rem;
        background: rgba(16,185,129,0.15);
        border: 1px solid rgba(16,185,129,0.4);
        padding: 0.4rem 1rem;
        border-radius: 999px;
        font-size: 0.8rem; font-weight: 800; color: #34d399;
        text-transform: uppercase; letter-spacing: 0.05em;
    }
    .op-live-dot {
        width: 8px; height: 8px; border-radius: 50%; background: #10b981;
        animation: blink 1.2s ease-in-out infinite;
    }

    .op-count {
        background: rgba(255,255,255,0.1);
        border: 1px solid rgba(255,255,255,0.15);
        padding: 0.4rem 1rem;
        border-radius: 999px;
        font-size: 0.85rem; font-weight: 700; color: #e0e7ff;
    }

    /* ── Stats bar ───────────────────────────────────────────── */
    .op-stats {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0.75rem;
        margin-bottom: 1.25rem;
    }

    @media (min-width: 640px) {
        .op-stats { grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.75rem; }
    }
    .op-stat {
        background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08);
        border-radius: 12px; padding: 0.85rem 1rem; text-align: center;
    }
    .op-stat-num  { font-size: 1.6rem; font-weight: 900; line-height: 1; }

    @media (min-width: 640px) {
        .op-stat { border-radius: 16px; padding: 1.1rem 1.25rem; }
        .op-stat-num { font-size: 2.2rem; }
    }
    .op-stat-lbl  { font-size: 0.75rem; color: rgba(255,255,255,0.45); font-weight: 600;
                    text-transform: uppercase; letter-spacing: 0.06em; margin-top: 4px; }
    .stat-pending  .op-stat-num { color: #f59e0b; }
    .stat-active   .op-stat-num { color: #60a5fa; }
    .stat-way      .op-stat-num { color: #a78bfa; }
    .stat-ready    .op-stat-num { color: #34d399; }

    /* ── New‐order alert banner ──────────────────────────────── */
    .op-alert {
        position: fixed; top: 1.5rem; left: 50%; transform: translateX(-50%);
        z-index: 9999;
        background: linear-gradient(135deg, #f59e0b, #ef4444);
        color: white;
        padding: 1rem 2rem;
        border-radius: 16px;
        font-size: 1.1rem; font-weight: 800;
        display: flex; align-items: center; gap: 0.75rem;
        box-shadow: 0 20px 60px rgba(245,158,11,0.5);
        animation: slideDown 0.4s cubic-bezier(.34,1.56,.64,1);
        cursor: pointer;
        white-space: nowrap;
    }
    .op-alert-bell { font-size: 1.5rem; animation: shake 0.5s ease-in-out infinite alternate; }

    @keyframes slideDown {
        from { transform: translateX(-50%) translateY(-100px); opacity: 0; }
        to   { transform: translateX(-50%) translateY(0); opacity: 1; }
    }
    @keyframes shake {
        from { transform: rotate(-15deg); }
        to   { transform: rotate(15deg); }
    }
    @keyframes blink {
        0%, 100% { opacity: 1; } 50% { opacity: 0.2; }
    }

    /* ── Columns layout ──────────────────────────────────────── */
    .op-columns {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
    }

    @media (min-width: 640px) {
        .op-columns { grid-template-columns: repeat(2, 1fr); gap: 1rem; }
    }

    @media (min-width: 1280px) {
        .op-columns { grid-template-columns: repeat(4, 1fr); gap: 1.25rem; }
    }

    .op-col-header {
        display: flex; align-items: center; gap: 0.6rem;
        padding: 0.75rem 1rem;
        border-radius: 12px;
        margin-bottom: 1rem;
        font-size: 0.8rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em;
    }
    .op-col-dot  { width: 10px; height: 10px; border-radius: 50%; }
    .op-col-badge {
        margin-left: auto;
        background: rgba(255,255,255,0.15);
        padding: 2px 8px; border-radius: 999px;
        font-size: 0.75rem;
    }

    .col-pending   { background: rgba(245,158,11,0.12); border: 1px solid rgba(245,158,11,0.25); color: #fbbf24; }
    .col-pending   .op-col-dot { background: #f59e0b; }
    .col-confirmed { background: rgba(96,165,250,0.12); border: 1px solid rgba(96,165,250,0.25); color: #93c5fd; }
    .col-confirmed .op-col-dot { background: #3b82f6; }
    .col-preparing { background: rgba(251,146,60,0.12); border: 1px solid rgba(251,146,60,0.25); color: #fdba74; }
    .col-preparing .op-col-dot { background: #f97316; }
    .col-sending   { background: rgba(167,139,250,0.12); border: 1px solid rgba(167,139,250,0.25); color: #c4b5fd; }
    .col-sending   .op-col-dot { background: #8b5cf6; }

    /* ── Order card ──────────────────────────────────────────── */
    .op-card {
        background: rgba(255,255,255,0.06);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 18px;
        overflow: hidden;
        margin-bottom: 1rem;
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        position: relative;
    }
    .op-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 40px rgba(0,0,0,0.4);
        border-color: rgba(255,255,255,0.2);
    }

    /* urgency accent stripe */
    .op-card::before {
        content: '';
        position: absolute; top: 0; left: 0; right: 0;
        height: 3px;
    }
    .op-card[data-urgency="new"]     ::before, .stripe-new     { background: linear-gradient(90deg, #10b981, #34d399); }
    .op-card[data-urgency="normal"]  ::before, .stripe-normal  { background: linear-gradient(90deg, #3b82f6, #60a5fa); }
    .op-card[data-urgency="warning"] ::before, .stripe-warning { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
    .op-card[data-urgency="urgent"]  ::before, .stripe-urgent  { background: linear-gradient(90deg, #ef4444, #f87171); animation: pulse-red 1s ease-in-out infinite; }

    .op-card-stripe { height: 4px; width: 100%; }
    .stripe-new     { background: linear-gradient(90deg, #10b981, #34d399); }
    .stripe-normal  { background: linear-gradient(90deg, #3b82f6, #60a5fa); }
    .stripe-warning { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
    .stripe-urgent  { background: linear-gradient(90deg, #ef4444, #f87171); animation: pulse-red 1s ease-in-out infinite; }

    @keyframes pulse-red { 0%,100%{opacity:1;} 50%{opacity:0.5;} }

    .op-card-body { padding: 1.1rem 1.25rem; }

    .op-card-top {
        display: flex; align-items: flex-start; justify-content: space-between;
        margin-bottom: 0.85rem;
    }
    .op-card-id   { font-size: 1.5rem; font-weight: 900; color: #e0e7ff; line-height: 1; }
    .op-card-id small { font-size: 0.7rem; color: rgba(255,255,255,0.4); font-weight: 600; display: block; }
    .op-card-timer {
        font-size: 0.85rem; font-weight: 800;
        padding: 3px 10px; border-radius: 999px;
        font-variant-numeric: tabular-nums;
    }
    .timer-new     { background: rgba(16,185,129,0.2); color: #34d399; }
    .timer-normal  { background: rgba(59,130,246,0.2); color: #93c5fd; }
    .timer-warning { background: rgba(245,158,11,0.2); color: #fbbf24; }
    .timer-urgent  { background: rgba(239,68,68,0.2);  color: #f87171; }

    .op-card-client {
        font-size: 0.9rem; color: rgba(255,255,255,0.6); font-weight: 600;
        margin-bottom: 0.75rem;
        display: flex; align-items: center; gap: 0.4rem;
    }

    /* items mini list */
    .op-items { margin-bottom: 0.85rem; }
    .op-item  { display: flex; align-items: center; gap: 0.6rem; padding: 3px 0; font-size: 0.82rem; }
    .op-item-qty {
        background: rgba(255,255,255,0.12);
        width: 22px; height: 22px; border-radius: 6px;
        display: flex; align-items: center; justify-content: center;
        font-weight: 800; font-size: 0.78rem; flex-shrink: 0;
    }
    .op-item-name { color: rgba(255,255,255,0.75); font-weight: 600; }

    /* total */
    .op-total {
        font-size: 1.05rem; font-weight: 800; color: #a5f3fc;
        margin-bottom: 0.85rem;
        display: flex; align-items: center; gap: 0.4rem;
    }

    /* action buttons */
    .op-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }
    .op-btn {
        flex: 1; min-width: 90px;
        display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem;
        padding: 0.55rem 0.75rem;
        border-radius: 10px;
        font-size: 0.8rem; font-weight: 800;
        border: none; cursor: pointer;
        transition: all 0.15s ease;
        text-transform: uppercase; letter-spacing: 0.04em;
    }
    .op-btn:hover  { transform: translateY(-1px); filter: brightness(1.1); }
    .op-btn:active { transform: scale(0.97); }

    .btn-confirm {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        box-shadow: 0 4px 15px rgba(16,185,129,0.3);
    }
    .btn-advance {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: white;
        box-shadow: 0 4px 15px rgba(59,130,246,0.3);
    }
    .btn-cancel {
        background: rgba(239,68,68,0.15);
        border: 1px solid rgba(239,68,68,0.3) !important;
        color: #f87171;
    }
    .btn-cancel:hover { background: rgba(239,68,68,0.25); }

    /* ── Modal overlay ───────────────────────────────────────── */
    .op-modal-bg {
        position: fixed; inset: 0; z-index: 1000;
        background: rgba(0,0,0,0.7); backdrop-filter: blur(8px);
        display: none; align-items: flex-end; justify-content: center; padding: 0;
    }
    .op-modal-bg.open { display: flex; }
    .op-modal {
        background: linear-gradient(135deg, #1e1b4b, #312e81);
        border: 1px solid rgba(255,255,255,0.12);
        border-radius: 20px 20px 0 0;
        padding: 1.5rem;
        width: 100%;
        max-width: 100%;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 -10px 40px rgba(0,0,0,0.4);
        animation: slideUp 0.3s cubic-bezier(.34,1.56,.64,1);
    }
    @media (min-width: 640px) {
        .op-modal-bg { align-items: center; padding: 1rem; }
        .op-modal {
            border-radius: 24px;
            padding: 2rem;
            max-width: 480px;
            max-height: none;
            box-shadow: 0 25px 80px rgba(0,0,0,0.6);
            animation: popIn 0.3s cubic-bezier(.34,1.56,.64,1);
        }
    }
    @keyframes slideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }
    @keyframes popIn {
        from { transform: scale(0.8); opacity: 0; }
        to   { transform: scale(1);   opacity: 1; }
    }
    .op-modal-title {
        font-size: 1.3rem; font-weight: 900; margin-bottom: 0.5rem; color: #e0e7ff;
    }
    .op-modal-sub {
        font-size: 0.85rem; color: rgba(255,255,255,0.5); margin-bottom: 1.5rem;
    }

    /* confirm modal items */
    .op-modal-items {
        background: rgba(0,0,0,0.2);
        border-radius: 14px; padding: 1rem;
        margin-bottom: 1.25rem;
    }
    .op-modal-item {
        display: flex; align-items: center; gap: 0.75rem;
        padding: 0.5rem 0;
        border-bottom: 1px solid rgba(255,255,255,0.05);
        font-size: 0.9rem;
    }
    .op-modal-item:last-child { border-bottom: none; }
    .op-modal-qty {
        background: rgba(99,102,241,0.3);
        width: 28px; height: 28px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        font-weight: 900; flex-shrink: 0; color: #a5b4fc;
    }
    .op-modal-total {
        font-size: 1.1rem; font-weight: 800; color: #34d399;
        margin-bottom: 1.5rem;
    }

    /* cancel modal textarea */
    .op-input {
        width: 100%;
        background: rgba(0,0,0,0.3);
        border: 1px solid rgba(255,255,255,0.15);
        border-radius: 12px;
        padding: 0.75rem 1rem;
        color: #e0e7ff;
        font-size: 0.9rem;
        font-family: inherit;
        resize: vertical;
        margin-bottom: 1.25rem;
        outline: none;
        transition: border-color 0.2s;
    }
    .op-input:focus { border-color: #6366f1; }

    .op-modal-actions { display: flex; gap: 0.75rem; }
    .op-modal-actions .op-btn { flex: 1; padding: 0.75rem; font-size: 0.85rem; }
    .btn-ghost {
        background: rgba(255,255,255,0.06);
        border: 1px solid rgba(255,255,255,0.1) !important;
        color: rgba(255,255,255,0.5);
    }

    /* ── Empty state ─────────────────────────────────────────── */
    .op-empty {
        text-align: center; padding: 4rem 2rem;
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.06);
        border-radius: 20px;
    }
    .op-empty-icon { font-size: 4rem; display: block; margin-bottom: 1rem; }
    .op-empty-title { font-size: 1.4rem; font-weight: 800; color: #e0e7ff; }
    .op-empty-sub   { font-size: 0.9rem; color: rgba(255,255,255,0.4); margin-top: 0.5rem; }
</style>

{{-- ═══════════════════ NEW-ORDER ALERT BANNER ═══════════════════ --}}
<div
    x-show="alertVisible"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 -translate-y-10"
    x-transition:enter-end="opacity-100 translate-y-0"
    @click="alertVisible = false; $wire.clearNewOrderAlert()"
    class="op-alert"
    style="display:none;"
>
    <span class="op-alert-bell">🔔</span>
    ¡Nuevo pedido entrante! — Haz clic para cerrar
</div>

{{-- ═══════════════════════ HEADER ════════════════════════════════ --}}
<div class="op-header">
    <div class="op-header-left">
        <div class="op-logo">🚀</div>
        <div>
            <h1 class="op-title">Panel de Operador</h1>
            <div class="op-sub">Gestión de pedidos en tiempo real</div>
        </div>
    </div>
    <div class="op-header-right">
        <span class="op-count">
            {{ collect($orders)->count() }} {{ collect($orders)->count() === 1 ? 'pedido activo' : 'pedidos activos' }}
        </span>
        <span class="op-live">
            <span class="op-live-dot"></span>
            En vivo
        </span>
    </div>
</div>

{{-- ═══════════════════════ STATS BAR ══════════════════════════════ --}}
@php
    $pending   = collect($orders)->where('status', 'pending')->count();
    $confirmed = collect($orders)->where('status', 'confirmed')->count();
    $preparing = collect($orders)->where('status', 'preparing')->count();
    $ready     = collect($orders)->where('status', 'ready_to_go')->count();
    $inWay     = collect($orders)->whereIn('status', ['assigned','on_way'])->count();
@endphp
<div class="op-stats">
    <div class="op-stat stat-pending">
        <div class="op-stat-num">{{ $pending }}</div>
        <div class="op-stat-lbl">⏳ Pendientes</div>
    </div>
    <div class="op-stat stat-active">
        <div class="op-stat-num">{{ $confirmed + $preparing }}</div>
        <div class="op-stat-lbl">🍳 En proceso</div>
    </div>
    <div class="op-stat stat-ready">
        <div class="op-stat-num">{{ $ready }}</div>
        <div class="op-stat-lbl">✅ Listos</div>
    </div>
    <div class="op-stat stat-way">
        <div class="op-stat-num">{{ $inWay }}</div>
        <div class="op-stat-lbl">🛵 En camino</div>
    </div>
</div>

{{-- ═══════════════════════ KANBAN COLUMNS ════════════════════════ --}}
@if(collect($orders)->isEmpty())
    <div class="op-empty">
        <span class="op-empty-icon">🎉</span>
        <div class="op-empty-title">¡Sin pedidos activos!</div>
        <div class="op-empty-sub">El sistema revisará automáticamente cada 5 segundos.</div>
    </div>
@else

{{-- Group orders by stage --}}
@php
    $groups = [
        'pending'   => ['label' => '⏳ Nuevos Pedidos',     'css' => 'col-pending',   'statuses' => ['pending']],
        'kitchen'   => ['label' => '🍳 En Cocina',          'css' => 'col-confirmed', 'statuses' => ['confirmed', 'preparing']],
        'ready'     => ['label' => '✅ Listos para Enviar',  'css' => 'col-preparing', 'statuses' => ['ready_to_go']],
        'delivery'  => ['label' => '🛵 En Camino',           'css' => 'col-sending',   'statuses' => ['assigned', 'on_way']],
    ];
@endphp

<div class="op-columns">
    @foreach($groups as $groupKey => $group)
        @php
            $groupOrders = collect($orders)->filter(fn($o) => in_array($o->status, $group['statuses']));
        @endphp
        <div>
            <div class="op-col-header {{ $group['css'] }}">
                <span class="op-col-dot"></span>
                {{ $group['label'] }}
                <span class="op-col-badge">{{ $groupOrders->count() }}</span>
            </div>

            @forelse($groupOrders as $order)
                @php
                    $ageSeconds = (int) $order->age_seconds;
                    if      ($ageSeconds < 120)  { $urgency = 'new';     $timerCss = 'timer-new';     $stripeCss = 'stripe-new'; }
                    elseif  ($ageSeconds < 300)  { $urgency = 'normal';  $timerCss = 'timer-normal';  $stripeCss = 'stripe-normal'; }
                    elseif  ($ageSeconds < 600)  { $urgency = 'warning'; $timerCss = 'timer-warning'; $stripeCss = 'stripe-warning'; }
                    else                         { $urgency = 'urgent';  $timerCss = 'timer-urgent';  $stripeCss = 'stripe-urgent'; }

                    $createdAtIso = $order->created_at->toIso8601String();
                    $itemsJson = json_encode($order->items->map(fn($i) => [
                        'qty'  => $i->quantity,
                        'name' => $i->product?->name ?? 'Producto',
                    ])->values()->toArray());
                @endphp

                <div class="op-card"
                     x-data="{ age: {{ $ageSeconds }}, createdAt: '{{ $createdAtIso }}' }"
                     x-init="setInterval(() => { age = getAgeSeconds(createdAt); }, 1000)"
                >
                    {{-- urgency stripe --}}
                    <div class="op-card-stripe {{ $stripeCss }}"
                         :class="{
                             'stripe-new':     urgency(getAgeSeconds(createdAt)) === 'new',
                             'stripe-normal':  urgency(getAgeSeconds(createdAt)) === 'normal',
                             'stripe-warning': urgency(getAgeSeconds(createdAt)) === 'warning',
                             'stripe-urgent':  urgency(getAgeSeconds(createdAt)) === 'urgent'
                         }"
                    ></div>

                    <div class="op-card-body">
                        {{-- ID + Timer --}}
                        <div class="op-card-top">
                            <div class="op-card-id">
                                <small>Pedido</small>
                                #{{ $order->id }}
                            </div>
                            <div class="op-card-timer"
                                 :class="{
                                     'timer-new':     urgency(getAgeSeconds(createdAt)) === 'new',
                                     'timer-normal':  urgency(getAgeSeconds(createdAt)) === 'normal',
                                     'timer-warning': urgency(getAgeSeconds(createdAt)) === 'warning',
                                     'timer-urgent':  urgency(getAgeSeconds(createdAt)) === 'urgent'
                                 }"
                                 x-text="formatAge(getAgeSeconds(createdAt))"
                            >{{ gmdate('i:s', $ageSeconds) }}</div>
                        </div>

                        {{-- Client --}}
                        <div class="op-card-client">
                            <svg style="width:14px;height:14px;flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            {{ $order->user?->name ?? 'Cliente' }}
                            @if($order->branch?->name)
                                <span style="color:rgba(255,255,255,0.25);">·</span>
                                {{ $order->branch->name }}
                            @endif
                        </div>

                        {{-- Items --}}
                        <div class="op-items">
                            @foreach($order->items->take(3) as $item)
                                <div class="op-item">
                                    <div class="op-item-qty">{{ $item->quantity }}</div>
                                    <div class="op-item-name">{{ $item->product?->name ?? 'Producto' }}</div>
                                </div>
                            @endforeach
                            @if($order->items->count() > 3)
                                <div class="op-item" style="color:rgba(255,255,255,0.35); font-size:0.75rem;">
                                    + {{ $order->items->count() - 3 }} producto(s) más
                                </div>
                            @endif
                        </div>

                        {{-- Total --}}
                        <div class="op-total">
                            <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                            </svg>
                            ${{ number_format($order->total, 2) }}
                        </div>

                        {{-- Actions --}}
                        <div class="op-actions">
                            @if($order->status === 'pending')
                                <button class="op-btn btn-confirm"
                                        @click="openConfirmModal(
                                            {{ $order->id }},
                                            '{{ addslashes($order->user?->name ?? 'Cliente') }}',
                                            '${{ number_format($order->total, 2) }}',
                                            {!! $itemsJson !!}
                                        )">
                                    <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Confirmar
                                </button>
                            @elseif(in_array($order->status, ['confirmed', 'preparing', 'ready_to_go', 'assigned']))
                                @php
                                    $nextLabels = [
                                        'confirmed'   => '→ Preparar',
                                        'preparing'   => '→ Listo',
                                        'ready_to_go' => '→ Asignar',
                                        'assigned'    => '→ En camino',
                                    ];
                                @endphp
                                <button class="op-btn btn-advance"
                                        wire:click="advanceOrder({{ $order->id }})"
                                        wire:loading.attr="disabled">
                                    <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                    </svg>
                                    {{ $nextLabels[$order->status] ?? 'Avanzar' }}
                                </button>
                            @endif

                            @if(!in_array($order->status, ['on_way']))
                                <button class="op-btn btn-cancel"
                                        @click="openCancelModal({{ $order->id }})">
                                    <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                    Cancelar
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div style="text-align:center; padding: 2rem 1rem; color: rgba(255,255,255,0.2); font-size:0.85rem;">
                    Sin pedidos en esta etapa
                </div>
            @endforelse
        </div>
    @endforeach
</div>
@endif

{{-- ═══════════════════ MODAL: CONFIRMAR PEDIDO ════════════════════ --}}
<div class="op-modal-bg" x-show="confirmModalOpen" x-transition style="display:none;">
    <div class="op-modal" @click.stop>
        <div class="op-modal-title">✅ Confirmar Pedido</div>
        <div class="op-modal-sub">Revisa el pedido antes de confirmar</div>

        <div style="font-size:0.8rem;color:rgba(255,255,255,0.4);margin-bottom:0.5rem;text-transform:uppercase;letter-spacing:0.06em;">
            Cliente
        </div>
        <div style="font-size:1rem;font-weight:700;color:#e0e7ff;margin-bottom:1rem;" x-text="confirmOrderName"></div>

        <div style="font-size:0.8rem;color:rgba(255,255,255,0.4);margin-bottom:0.5rem;text-transform:uppercase;letter-spacing:0.06em;">
            Productos
        </div>
        <div class="op-modal-items">
            <template x-for="item in confirmOrderItems" :key="item.name">
                <div class="op-modal-item">
                    <div class="op-modal-qty" x-text="item.qty"></div>
                    <div style="font-size:0.9rem;color:rgba(255,255,255,0.7);" x-text="item.name"></div>
                </div>
            </template>
        </div>

        <div class="op-modal-total">
            Total: <span x-text="confirmOrderTotal"></span>
        </div>

        <div class="op-modal-actions">
            <button class="op-btn btn-ghost" @click="confirmModalOpen = false">Cancelar</button>
            <button class="op-btn btn-confirm" @click="submitConfirm()">
                <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
                Confirmar Pedido
            </button>
        </div>
    </div>
</div>

{{-- ═══════════════════ MODAL: CANCELAR PEDIDO ════════════════════ --}}
<div class="op-modal-bg" x-show="cancelModalOpen" x-transition style="display:none;">
    <div class="op-modal" @click.stop>
        <div class="op-modal-title">❌ Cancelar Pedido</div>
        <div class="op-modal-sub">Indica el motivo de cancelación (requerido)</div>

        <textarea
            class="op-input"
            x-model="cancelReason"
            placeholder="Ej: Producto agotado, cliente no responde..."
            rows="3"
        ></textarea>

        <div class="op-modal-actions">
            <button class="op-btn btn-ghost" @click="cancelModalOpen = false">Volver</button>
            <button class="op-btn btn-cancel"
                    :disabled="!cancelReason.trim()"
                    :style="!cancelReason.trim() ? 'opacity:0.5;cursor:not-allowed;' : ''"
                    @click="submitCancel()">
                Cancelar Pedido
            </button>
        </div>
    </div>
</div>

</div>
