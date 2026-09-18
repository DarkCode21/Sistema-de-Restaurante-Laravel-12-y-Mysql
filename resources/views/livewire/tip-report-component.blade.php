<div class="space-y-6">
    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-emerald-600">Control de propinas</p>
                <h1 class="mt-1 text-2xl font-black tracking-tight text-slate-900">Propinas por mozo</h1>
                <p class="mt-1 text-sm text-slate-500">Propinas cobradas en la sede activa, listas para su entrega.</p>
            </div>
            <div class="grid gap-3 sm:grid-cols-2 lg:w-[34rem]">
                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Desde
                    <input type="date" wire:model.live="fromDate" class="mt-1 block w-full rounded-xl border-slate-200 bg-slate-50 text-sm font-semibold text-slate-700 focus:border-emerald-500 focus:ring-emerald-500/20">
                </label>
                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Hasta
                    <input type="date" wire:model.live="toDate" class="mt-1 block w-full rounded-xl border-slate-200 bg-slate-50 text-sm font-semibold text-slate-700 focus:border-emerald-500 focus:ring-emerald-500/20">
                </label>
                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Buscar mozo
                    <input type="search" wire:model.live.debounce.250ms="waiterSearch" placeholder="Nombre del mozo" class="mt-1 block w-full rounded-xl border-slate-200 bg-slate-50 text-sm font-semibold text-slate-700 placeholder:font-normal focus:border-emerald-500 focus:ring-emerald-500/20">
                </label>
                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Mozo
                    <select wire:model.live="waiterId" class="mt-1 block w-full rounded-xl border-slate-200 bg-slate-50 text-sm font-semibold text-slate-700 focus:border-emerald-500 focus:ring-emerald-500/20">
                        <option value="">Todos los mozos</option>
                        @foreach ($waiters as $waiter)
                            <option value="{{ $waiter->id }}">{{ $waiter->name }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
        </div>
    </section>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <section class="relative overflow-hidden rounded-2xl border border-white/20 bg-gradient-to-br from-emerald-500 to-teal-600 p-5 text-white shadow-[0_8px_30px_rgb(16,185,129,0.25)]">
            <i class="fa-solid fa-hand-holding-heart absolute -right-3 -top-4 text-8xl text-white/10"></i>
            <p class="relative text-[10px] font-black uppercase tracking-widest text-emerald-100">Propinas acumuladas</p>
            <p class="relative mt-2 text-3xl font-black tracking-tight">{{ $empresa->currency_simbol }}{{ number_format($totalTips, 2) }}</p>
        </section>
        <section class="relative overflow-hidden rounded-2xl border border-white/20 bg-gradient-to-br from-slate-700 to-slate-900 p-5 text-white shadow-[0_8px_30px_rgb(15,23,42,0.2)]">
            <i class="fa-solid fa-receipt absolute -right-3 -top-4 text-8xl text-white/10"></i>
            <p class="relative text-[10px] font-black uppercase tracking-widest text-slate-300">Ventas con propina</p>
            <p class="relative mt-2 text-3xl font-black tracking-tight">{{ $salesCount }}</p>
        </section>
        <section class="relative overflow-hidden rounded-2xl border border-white/20 bg-gradient-to-br from-orange-500 to-amber-600 p-5 text-white shadow-[0_8px_30px_rgb(249,115,22,0.25)]">
            <i class="fa-solid fa-hourglass-half absolute -right-3 -top-4 text-8xl text-white/10"></i>
            <p class="relative text-[10px] font-black uppercase tracking-widest text-orange-100">Pendiente de entregar</p>
            <p class="relative mt-2 text-3xl font-black tracking-tight">{{ $empresa->currency_simbol }}{{ number_format($totalPending, 2) }}</p>
        </section>
        <section class="relative overflow-hidden rounded-2xl border border-white/20 bg-gradient-to-br from-violet-500 to-purple-700 p-5 text-white shadow-[0_8px_30px_rgb(139,92,246,0.25)]">
            <i class="fa-solid fa-arrow-up-right-dots absolute -right-3 -top-4 text-8xl text-white/10"></i>
            <p class="relative text-[10px] font-black uppercase tracking-widest text-violet-100">Desembolsado en periodo</p>
            <p class="relative mt-2 text-3xl font-black tracking-tight">{{ $empresa->currency_simbol }}{{ number_format($totalPayouts, 2) }}</p>
        </section>
    </div>

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-6 py-4">
            <h2 class="font-black text-slate-800">Resumen por mozo</h2>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse ($byWaiter as $row)
                <div class="flex flex-col gap-3 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div><p class="font-bold text-slate-700">{{ $row->name }}</p><p class="mt-1 text-xs text-slate-400">{{ $row->sales_count }} venta{{ $row->sales_count == 1 ? '' : 's' }} con propina en este periodo</p></div>
                    <div class="flex items-center gap-5 sm:text-right">
                        <div><p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Periodo</p><p class="text-lg font-black text-emerald-600">{{ $empresa->currency_simbol }}{{ number_format($row->total_tips, 2) }}</p></div>
                        <div><p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Pendiente</p><p class="text-lg font-black text-orange-600">{{ $empresa->currency_simbol }}{{ number_format($row->pending_tips, 2) }}</p></div>
                        @if ($row->pending_tips > 0)
                            <button wire:click="openPayout({{ $row->waiter_id }})" class="rounded-xl bg-slate-900 px-4 py-2 text-[10px] font-black uppercase tracking-wider text-white transition hover:bg-orange-600">Desembolsar</button>
                        @endif
                    </div>
                </div>
            @empty
                <p class="px-6 py-10 text-center text-sm text-slate-400">No hay propinas en el periodo seleccionado.</p>
            @endforelse
        </div>
    </section>

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-6 py-4"><h2 class="font-black text-slate-800">Detalle de propinas</h2></div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-[10px] font-black uppercase tracking-widest text-slate-400"><tr><th class="px-6 py-4">Venta</th><th class="px-6 py-4">Mozo</th><th class="px-6 py-4">Caja</th><th class="px-6 py-4 text-right">Propina</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($sales as $sale)
                        <tr class="hover:bg-slate-50/70"><td class="px-6 py-4"><p class="font-black text-slate-700">#{{ $sale->id }}</p><p class="mt-1 text-xs text-slate-400">{{ $sale->paid_at->format('d/m/Y H:i') }}</p></td><td class="px-6 py-4 font-semibold text-slate-600">{{ $sale->order?->user?->name ?? 'Sin mozo' }}</td><td class="px-6 py-4 text-slate-500">{{ $sale->cashRegister?->name ?? 'Sin caja' }}</td><td class="px-6 py-4 text-right text-base font-black text-emerald-600">{{ $empresa->currency_simbol }}{{ number_format($sale->adjusted_tip, 2) }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="px-6 py-10 text-center text-sm text-slate-400">No hay propinas en el periodo seleccionado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 bg-slate-50/40 px-6 py-4">{{ $sales->links() }}</div>
    </section>

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-6 py-4"><h2 class="font-black text-slate-800">Desembolsos registrados</h2></div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-[10px] font-black uppercase tracking-widest text-slate-400"><tr><th class="px-6 py-4">Fecha</th><th class="px-6 py-4">Mozo</th><th class="px-6 py-4">Medio</th><th class="px-6 py-4">Registró</th><th class="px-6 py-4 text-right">Monto</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($payouts as $payout)
                        <tr class="hover:bg-slate-50/70"><td class="px-6 py-4 text-slate-500">{{ $payout->paid_at->format('d/m/Y H:i') }}</td><td class="px-6 py-4 font-semibold text-slate-700">{{ $payout->waiter_name }}</td><td class="px-6 py-4"><p class="font-semibold text-slate-600">{{ $payout->paymentMethod?->name ?? 'Método histórico' }}</p><p class="mt-1 text-xs text-slate-400">{{ $payout->cashRegister?->name ?? $payout->reference ?? 'Sin referencia' }}</p></td><td class="px-6 py-4 text-slate-500">{{ $payout->paidBy?->name ?? 'Usuario histórico' }}</td><td class="px-6 py-4 text-right text-base font-black text-violet-600">{{ $empresa->currency_simbol }}{{ number_format($payout->amount, 2) }}</td></tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-10 text-center text-sm text-slate-400">No hay desembolsos en el periodo seleccionado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 bg-slate-50/40 px-6 py-4">{{ $payouts->links() }}</div>
    </section>

    @if ($showPayoutModal)
        <div class="fixed inset-0 z-[100] grid place-items-center p-4">
            <div class="absolute inset-0 bg-slate-950/60" wire:click="closePayout"></div>
            <section class="relative w-full max-w-lg rounded-3xl bg-white p-6 shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-4"><div><p class="text-[10px] font-black uppercase tracking-[0.18em] text-violet-600">Entrega de propina</p><h2 class="mt-1 text-xl font-black text-slate-900">Registrar desembolso</h2></div><button wire:click="closePayout" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100"><i class="fa-solid fa-xmark"></i></button></div>
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Mozo
                        <select wire:model="payoutWaiterId" class="mt-1 block w-full rounded-xl border-slate-200 bg-slate-50 text-sm font-semibold text-slate-700"><option value="">Selecciona un mozo</option>@foreach ($waiters as $waiter)<option value="{{ $waiter->id }}">{{ $waiter->name }}</option>@endforeach</select>
                        @error('payoutWaiterId')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                    </label>
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Medio de pago
                        <select wire:model.live="payoutPaymentMethodId" class="mt-1 block w-full rounded-xl border-slate-200 bg-slate-50 text-sm font-semibold text-slate-700"><option value="">Selecciona un medio</option>@foreach ($paymentMethods as $method)<option value="{{ $method->id }}">{{ $method->name }}</option>@endforeach</select>
                        @error('payoutPaymentMethodId')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                    </label>
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Monto
                        <input type="number" min="0.01" step="0.01" wire:model="payoutAmount" class="mt-1 block w-full rounded-xl border-slate-200 bg-slate-50 text-sm font-semibold text-slate-700">
                        @error('payoutAmount')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                    </label>
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Referencia
                        <input type="text" maxlength="255" wire:model="payoutReference" placeholder="Yape, operación o nota" class="mt-1 block w-full rounded-xl border-slate-200 bg-slate-50 text-sm font-semibold text-slate-700">
                        @error('payoutReference')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                    </label>
                    @if ($selectedPayoutMethod?->is_efectivo)
                        <label class="sm:col-span-2 text-[10px] font-black uppercase tracking-widest text-slate-400">Caja que entrega el efectivo
                            <select wire:model="payoutCashRegisterId" class="mt-1 block w-full rounded-xl border-slate-200 bg-slate-50 text-sm font-semibold text-slate-700"><option value="">Selecciona una caja abierta</option>@foreach ($cashRegisters as $cashRegister)<option value="{{ $cashRegister->id }}">{{ $cashRegister->name }} · {{ $empresa->currency_simbol }}{{ number_format($cashRegister->current_amount, 2) }}</option>@endforeach</select>
                            @error('payoutCashRegisterId')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                        </label>
                    @endif
                </div>
                <p class="mt-4 rounded-xl bg-slate-50 px-4 py-3 text-xs text-slate-500">Efectivo descuenta el saldo físico de la caja. Yape, transferencia y otros medios quedan auditados sin afectar efectivo.</p>
                <button wire:click="savePayout" class="mt-5 w-full rounded-xl bg-violet-600 px-4 py-3 text-xs font-black uppercase tracking-widest text-white transition hover:bg-violet-700">Confirmar desembolso</button>
            </section>
        </div>
    @endif
</div>
