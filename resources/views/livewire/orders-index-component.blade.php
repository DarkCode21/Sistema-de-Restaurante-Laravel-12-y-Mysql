<div wire:poll.5s.keep-alive="refreshReadyOrderAlerts" class="p-6 bg-[#fcfcfc] min-h-screen font-sans text-slate-900 antialiased">
    <div class="mx-auto">

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-black tracking-tighter text-slate-800">MONITOR DE DESPACHO</h1>

                <!-- Contenedor de Leyendas (Badges) -->
                <div
                    class="flex flex-wrap items-center gap-3 mt-3 bg-slate-100 border border-slate-200/60 rounded-xl px-3 py-2 w-fit shadow-sm">
                    <!-- Leyenda: Atendido -->
                    <div class="flex items-center gap-2">
                        <span
                            class="bg-emerald-500 text-white w-5 h-5 rounded-md flex items-center justify-center shrink-0 shadow-sm">
                            <i class="fa-solid fa-check text-[10px]"></i>
                        </span>
                        <span class="text-[11px] font-bold text-slate-600 uppercase tracking-tight">
                            Retirar y entregar solo platos listos
                        </span>
                    </div>

                    <!-- Separador sutil -->
                    <span class="h-4 w-[1px] bg-slate-300 hidden sm:block"></span>

                    <!-- Leyenda: Cancelar -->
                    <div class="flex items-center gap-2">
                        <span
                            class="bg-rose-500 text-white w-5 h-5 rounded-md flex items-center justify-center shrink-0 shadow-sm">
                            <i class="fa-solid fa-xmark text-[10px]"></i>
                        </span>
                        <span class="text-[11px] font-bold text-slate-600 uppercase tracking-tight">
                            Cancelar orden / ítem
                        </span>
                    </div>
                </div>
            </div>

            <!-- Buscador -->
            <div class="flex items-center gap-3">
                <button type="button" wire:ignore data-waiter-bell-toggle
                    class="shrink-0 inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-black text-emerald-700 transition-colors hover:bg-emerald-100"
                    title="Silenciar campana" aria-label="Campana de avisos" aria-pressed="true">
                    <i class="fa-solid fa-bell"></i>
                    <span class="hidden sm:inline" data-waiter-bell-label>Silenciar campana</span>
                </button>
                <input wire:model.live="search" type="text" placeholder="Buscar mesa..."
                    class="bg-white border border-slate-200 py-2 px-4 rounded-xl text-sm outline-none focus:ring-2 focus:ring-orange-500/20 transition-all w-64 shadow-sm">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-2 2xl:grid-cols-3 gap-6">
            @forelse ($orders as $order)
                <div wire:key="order-{{ $order->id }}"
                    class="bg-white border border-slate-200 rounded-3xl flex flex-col shadow-sm hover:shadow-md transition-all overflow-hidden relative">

                    <div class="p-4 border-b border-slate-50 bg-slate-50/50 flex justify-between items-center">
                        <div>
                            <span class="text-[10px] font-black text-orange-600 uppercase tracking-tighter">Mesa</span>
                            <h2 class="text-2xl font-black leading-none text-slate-800">{{ $order->table->name }}</h2>
                        </div>
                        <div class="text-right">
                            <span
                                class="text-[10px] font-bold text-slate-400 block">{{ $order->created_at->format('H:i A') }}</span>
                            <span
                                class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase bg-orange-100 text-orange-700">
                                {{ $order->status }}
                            </span>
                        </div>
                    </div>

                    {{-- CUERPO: PRODUCTOS --}}
                    <div class="p-4 flex-1 space-y-5 max-h-[400px] overflow-y-auto custom-scrollbar">
                        @foreach ($order->details as $detail)
                            <div
                                class="group relative bg-white p-3 pt-5 rounded-2xl border border-slate-100 shadow-sm transition-all">

                                <div class="absolute -top-2.5 left-3">
                                    @if (!$detail->requires_kitchen && $detail->cooking_status !== 'served')
                                        <span
                                            class="bg-blue-100 text-blue-700 text-[8px] font-black px-2 py-0.5 rounded-md uppercase tracking-widest">Para entregar</span>
                                    @elseif ($detail->cooking_status === 'in_progress')
                                        <span
                                            class="bg-amber-500 text-white text-[8px] font-black px-2 py-0.5 rounded-md uppercase tracking-widest shadow-sm animate-pulse">En
                                            Proceso
                                        </span>
                                    @elseif ($detail->cooking_status === 'pending')
                                        <span
                                            class="bg-slate-200 text-slate-500 text-[8px] font-black px-2 py-0.5 rounded-md uppercase tracking-widest">Pendiente</span>
                                    @elseif($detail->cooking_status === 'ready')
                                        <span
                                            class="bg-emerald-500 text-white text-[8px] font-black px-2 py-0.5 rounded-md uppercase tracking-widest shadow-sm">Listo</span>
                                    @else
                                        <span
                                            class="bg-slate-200 text-slate-500 text-[8px] font-black px-2 py-0.5 rounded-md uppercase tracking-widest">Entregado</span>
                                    @endif
                                </div>

                                <div class="flex items-center justify-between gap-3">
                                    <div class="flex items-start gap-3 min-w-0 flex-1">
                                        <span
                                            class="bg-slate-100 text-slate-700 text-[10px] font-black w-6 h-6 rounded-lg flex items-center justify-center shrink-0">
                                            {{ $detail->quantity }}
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <p
                                                class="text-sm font-black text-slate-800 uppercase leading-snug break-words">
                                                {{ $detail->product->name }}
                                            </p>
                                            @if ($detail->notes)
                                                <p
                                                    class="text-[10px] text-orange-500 font-bold italic mt-1 leading-tight uppercase bg-orange-50/50 p-1 rounded-lg">
                                                    "{{ $detail->notes }}"
                                                </p>
                                            @endif
                                        </div>
                                    </div>

                                    @if ($detail->cooking_status === 'ready' || !$detail->requires_kitchen)
                                        <div class="shrink-0 flex flex-col items-end gap-2">
                                            <button wire:click="markDetailAsServed({{ $detail->id }})"
                                                title="Marcar como entregado"
                                                aria-label="Marcar como entregado {{ $detail->product->name }}"
                                                class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-50 px-3 py-2 text-[10px] font-black uppercase tracking-tight text-emerald-700 transition-all hover:bg-emerald-600 hover:text-white active:scale-95">
                                                <i class="fa-solid fa-check-double text-xs"></i>
                                                Entregar
                                            </button>
                                            <button wire:click="cancelarDetalle({{ $detail->id }})"
                                                title="Cancelar pedido"
                                                class="bg-slate-100 hover:bg-red-500 text-slate-700 hover:text-white w-8 h-8 rounded-xl flex items-center justify-center transition-all active:scale-90 border border-slate-200/60 shadow-sm">
                                                <i class="fa-solid fa-xmark text-xs"></i>
                                            </button>
                                        </div>
                                    @elseif ($detail->cooking_status !== 'served')
                                        <div class="shrink-0">
                                            <button wire:click="cancelarDetalle({{ $detail->id }})"
                                                title="Cancelar pedido"
                                                class="bg-slate-100 hover:bg-red-500 text-slate-700 hover:text-white w-8 h-8 rounded-xl flex items-center justify-center transition-all active:scale-90 border border-slate-200/60 shadow-sm">
                                                <i class="fa-solid fa-xmark text-xs"></i>
                                            </button>
                                        </div>
                                    @endif
                                </div>

                            </div>
                        @endforeach
                    </div>

                    {{-- FOOTER --}}
                    <div class="p-4 bg-slate-50/80 border-t border-slate-50 mt-auto">
                        <div class="flex justify-between items-center">
                            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter">
                                ID: #{{ $order->id }} | {{ $order->user->name ?? 'Sistema' }}
                            </span>
                            <span class="text-sm font-black text-slate-900">
                                {{ $empresa->currency_simbol }}{{ number_format($order->total, 2) }}
                            </span>
                        </div>
                    </div>

                    <div class="grid gap-2 p-3 pt-0">
                        @can('ordenes.cobrar')
                            @if ($order->is_ready_for_checkout)
                                <a href="{{ route('orders.cashier', ['order' => $order->id, 'quick_checkout' => 1]) }}"
                                    class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs py-2.5 px-4 rounded-xl flex items-center justify-center gap-2 transition-all active:scale-[0.98] shadow-sm">
                                    <i class="fa-solid fa-cash-register text-[11px]"></i>
                                    Cobrar y liberar mesa
                                </a>
                            @endif
                        @endcan

                        @can('ordenes.crear')
                            <a href="{{ route('orders.create', encrypt($order->table_id)) }}"
                                class="w-full bg-slate-900 hover:bg-orange-600 text-white font-bold text-xs py-2 px-4 rounded-xl flex items-center justify-center gap-2 transition-all active:scale-[0.98] shadow-sm">
                                <i class="fa-solid fa-pen-to-square text-[11px]"></i>
                                Ver / editar pedido
                            </a>
                        @endcan
                    </div>
                </div>
            @empty
                <div
                    class="col-span-1 md:col-span-2 xl:col-span-2 2xl:col-span-3 bg-white border border-dashed border-slate-300 rounded-3xl p-12 text-center">
                    <div class="flex flex-col items-center justify-center gap-3 max-w-sm mx-auto">
                        <div
                            class="h-14 w-14 rounded-2xl bg-slate-50 text-slate-400 flex items-center justify-center text-2xl shadow-sm">
                            <i class="fa-solid fa-receipt"></i>
                        </div>
                        <h3 class="text-base font-black text-slate-800 uppercase tracking-tight">No hay órdenes
                            disponibles</h3>
                        <p class="text-xs text-slate-400 leading-normal">Actualmente no se encontraron registros de
                            pedidos en esta sección. Las nuevas comandas aparecerán automáticamente aquí.</p>
                    </div>
                </div>
            @endforelse
        </div>

        <div class="mt-8">
            {{ $orders->links() }}
        </div>
    </div>

<div id="waiter-ready-toast" class="fixed right-4 top-4 z-[120] hidden max-w-sm rounded-2xl border border-emerald-200 bg-white p-4 shadow-xl" role="status" aria-live="polite">
    <div class="flex items-start gap-3">
        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600">
            <i class="fa-solid fa-bell"></i>
        </span>
        <div>
            <p class="text-sm font-black text-slate-800">Pedido listo para entregar</p>
            <p class="text-xs font-semibold text-slate-500" data-waiter-ready-message></p>
        </div>
    </div>
</div>

<x-waiter-bell-listeners />
</div>
