<x-admin-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Auditoría</h2>
    </x-slot>

    <main class="w-full p-4 sm:p-6">
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-5 sm:px-7">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-orange-600">Trazabilidad</p>
                <h1 class="mt-1 text-2xl font-black text-slate-900">Cambios y pagos</h1>
                <p class="mt-1 text-sm text-slate-500">Correcciones de pedidos y cobros registrados.</p>
            </div>

            <form method="GET" class="grid gap-3 border-b border-slate-100 bg-slate-50 p-4 sm:grid-cols-4 sm:px-7">
                <input name="search" value="{{ $filters['search'] ?? '' }}" type="search" placeholder="Mesa, cliente, nota o número" class="rounded-lg border-slate-200 text-sm focus:border-orange-500 focus:ring-orange-500">
                <select name="type" class="rounded-lg border-slate-200 text-sm focus:border-orange-500 focus:ring-orange-500">
                    <option value="">Todos los movimientos</option>
                    <option value="correction" @selected(($filters['type'] ?? '') === 'correction')>Cambios de pedido</option>
                    <option value="payment" @selected(($filters['type'] ?? '') === 'payment')>Pagos</option>
                    <option value="payment_edit" @selected(($filters['type'] ?? '') === 'payment_edit')>Pagos corregidos</option>
                    @if ($tipsEnabled)
                        <option value="tip" @selected(($filters['type'] ?? '') === 'tip')>Ajustes de propina</option>
                    @endif
                </select>
                <input name="from" value="{{ $filters['from'] ?? '' }}" type="date" aria-label="Desde" class="rounded-lg border-slate-200 text-sm focus:border-orange-500 focus:ring-orange-500">
                <div class="flex gap-2">
                    <input name="to" value="{{ $filters['to'] ?? '' }}" type="date" aria-label="Hasta" class="min-w-0 flex-1 rounded-lg border-slate-200 text-sm focus:border-orange-500 focus:ring-orange-500">
                    <button class="rounded-lg bg-slate-900 px-4 text-xs font-black text-white hover:bg-orange-600">Filtrar</button>
                </div>
            </form>

            <div class="overflow-x-auto">
                <table class="w-full min-w-full divide-y divide-slate-100 text-left">
                    <thead class="bg-white text-[10px] font-black uppercase tracking-wider text-slate-400">
                        <tr>
                            <th class="px-5 py-3 sm:px-7">Fecha</th>
                            <th class="px-5 py-3">Movimiento</th>
                            <th class="px-5 py-3">Detalle</th>
                            <th class="px-5 py-3">Responsable</th>
                            <th class="px-5 py-3 text-right sm:px-7">Monto</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        @forelse ($events as $event)
                            <tr class="align-top hover:bg-slate-50">
                                <td class="whitespace-nowrap px-5 py-4 text-xs text-slate-500 sm:px-7">{{ \Illuminate\Support\Carbon::parse($event->occurred_at)->format('d/m/Y H:i') }}</td>
                                <td class="px-5 py-4">
                                    <span @class([
                                        'rounded-full px-2 py-1 text-[10px] font-black uppercase tracking-wide',
                                        'bg-amber-50 text-amber-700' => $event->event_type === 'correction',
                                        'bg-emerald-50 text-emerald-700' => $event->event_type === 'payment',
                                        'bg-violet-50 text-violet-700' => $event->event_type === 'payment_edit',
                                        'bg-sky-50 text-sky-700' => $event->event_type === 'tip',
                                    ])>{{ $event->event_type === 'correction' ? 'Pedido' : ($event->event_type === 'payment_edit' ? 'Pago corregido' : ($event->event_type === 'payment' ? 'Pago' : 'Propina')) }}</span>
                                    <p class="mt-2 text-xs font-bold text-slate-600">{{ $event->event_type === 'correction' ? ucfirst($event->action) : ($event->event_type === 'payment_edit' ? 'Corrección registrada' : ($event->event_type === 'payment' ? 'Cobro registrado' : 'Ajuste registrado')) }}</p>
                                </td>
                                <td class="max-w-sm px-5 py-4 text-slate-700">
                                    <p class="font-bold">{{ $event->event_type === 'correction' ? "Pedido #{$event->reference}" : "Venta #{$event->reference}" }}{{ $event->context ? " · {$event->context}" : '' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $event->subject ?: $event->notes ?: 'Sin referencia' }}{{ $event->method ? " · {$event->method}" : '' }}</p>
                                    @if ($event->subject && $event->notes)
                                        <p class="mt-1 text-xs text-slate-400">{{ $event->notes }}</p>
                                    @endif
                                    @if ($event->event_type === 'payment_edit')
                                        <p class="mt-1 text-xs text-violet-600">Antes: {{ $event->previous_method }} · {{ $currencySymbol }}{{ number_format((float) $event->previous_amount, 2) }}</p>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-xs text-slate-500">{{ $event->actor ?: 'No registrado' }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-right font-black text-slate-800 sm:px-7">{{ $event->amount === null ? '—' : $currencySymbol . number_format((float) $event->amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-12 text-center text-sm text-slate-400">No hay movimientos con esos filtros.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($events->hasPages())
                <div class="border-t border-slate-100 px-5 py-4 sm:px-7">{{ $events->links() }}</div>
            @endif
        </section>
    </main>
</x-admin-layout>
