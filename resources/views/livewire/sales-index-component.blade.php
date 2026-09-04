<div class="min-h-screen antialiased">
    <div class="mx-auto">

        <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm mb-6">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <div>
                    <h1 class="font-black tracking-tighter text-slate-800 uppercase flex items-center gap-2">
                        <i class="fas fa-file-invoice-dollar text-orange-600"></i>
                        Ventas
                    </h1>
                </div>

                <div class="flex flex-wrap items-end gap-3">
                    <div class="flex-1 min-w-[140px]">
                        <label class="text-[10px] font-black text-slate-400 uppercase ml-1 mb-1 block">Desde</label>
                        <div class="relative">
                            <i
                                class="fas fa-calendar-alt absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            <input type="date" wire:model.live="fromDate"
                                class="w-full bg-slate-50 border border-slate-200 py-2 pl-9 pr-3 rounded-xl text-sm outline-none focus:ring-2 focus:ring-orange-500/20 transition-all">
                        </div>
                    </div>

                    <div class="flex-1 min-w-[140px]">
                        <label class="text-[10px] font-black text-slate-400 uppercase ml-1 mb-1 block">Hasta</label>
                        <div class="relative">
                            <i
                                class="fas fa-calendar-alt absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            <input type="date" wire:model.live="toDate"
                                class="w-full bg-slate-50 border border-slate-200 py-2 pl-9 pr-3 rounded-xl text-sm outline-none focus:ring-2 focus:ring-orange-500/20 transition-all">
                        </div>
                    </div>

                    <div class="flex-1 min-w-[180px]">
                        <label class="text-[10px] font-black text-slate-400 uppercase ml-1 mb-1 block">Venta o cliente</label>
                        <div class="relative">
                            <i
                                class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            <input wire:model.live="search" type="text" placeholder="Mesa, cliente o # venta"
                                class="w-full bg-slate-50 border border-slate-200 py-2 pl-9 pr-4 rounded-xl text-sm outline-none focus:ring-2 focus:ring-orange-500/20 transition-all">
                        </div>
                    </div>

                    @can('ventas.reportes')
                        <div class="flex gap-2 w-full sm:w-auto">
                            <a href="{{ route('sales.report.pdf', ['search' => $search, 'from' => $fromDate, 'to' => $toDate]) }}"
                                target="_blank"
                                class="flex-1 sm:flex-none bg-rose-600 hover:bg-rose-700 text-white px-4 py-2.5 rounded-xl transition-all shadow-sm flex items-center justify-center gap-2 text-xs font-black uppercase">
                                <i class="fas fa-file-pdf"></i> PDF
                            </a>

                            <a href="{{ route('sales.report.excel', ['search' => $search, 'from' => $fromDate, 'to' => $toDate]) }}"
                                target="_blank"
                                class="flex-1 sm:flex-none bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2.5 rounded-xl transition-all shadow-sm flex items-center justify-center gap-2 text-xs font-black uppercase">
                                <i class="fas fa-file-excel"></i> EXCEL
                            </a>
                        </div>
                    @endcan
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 mb-6 sm:grid-cols-2">

            <div
                class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-cyan-500 to-blue-600 p-5 shadow-[0_8px_30px_rgb(6,182,212,0.3)] border border-white/20 transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_12px_40px_rgb(6,182,212,0.4)]">
                <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-white/10 blur-xl"></div>

                <div class="flex items-center justify-between relative z-10">
                    <div class="space-y-1">
                        <p class="text-[11px] font-bold text-cyan-100 uppercase tracking-widest drop-shadow-sm">Total
                            Ventas</p>
                        <h2 class="text-2xl font-black text-white tracking-tight drop-shadow-md">
                            {{ $empresa->currency_simbol }}{{ number_format($totalSales, 2) }}
                        </h2>
                    </div>
                    <div
                        class="h-11 w-11 rounded-xl bg-white/15 backdrop-blur-md border border-white/20 text-white flex items-center justify-center text-lg shadow-inner">
                        <i class="fas fa-cash-register"></i>
                    </div>
                </div>
            </div>

            <div
                class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 p-5 shadow-[0_8px_30px_rgb(16,185,129,0.3)] border border-white/20 transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_12px_40px_rgb(16,185,129,0.4)]">
                <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-white/10 blur-xl"></div>

                <div class="flex items-center justify-between relative z-10">
                    <div class="space-y-1">
                        <p class="text-[11px] font-bold text-emerald-100 uppercase tracking-widest drop-shadow-sm">Total
                            Propinas</p>
                        <h2 class="text-2xl font-black text-white tracking-tight drop-shadow-md">
                            {{ $empresa->currency_simbol }}{{ number_format($totalTips, 2) }}
                        </h2>
                    </div>
                    <div
                        class="h-11 w-11 rounded-xl bg-white/15 backdrop-blur-md border border-white/20 text-white flex items-center justify-center text-lg shadow-inner">
                        <i class="fas fa-hand-holding-heart"></i>
                    </div>
                </div>
            </div>

        </div>

        @if ($paymentTotals['cash'] > 0 || $paymentTotals['yape'] > 0 || $paymentTotals['card'] > 0)
            <div class="grid grid-cols-1 gap-4 mb-6 sm:grid-cols-2 xl:grid-cols-3">
            @if ($paymentTotals['cash'] > 0)
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 p-5 shadow-[0_8px_30px_rgb(245,158,11,0.3)] border border-white/20 transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_12px_40px_rgb(245,158,11,0.4)]">
                <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-white/10 blur-xl"></div>
                <div class="relative z-10 flex items-center justify-between">
                    <div class="space-y-1"><p class="text-[11px] font-bold uppercase tracking-widest text-amber-100">Efectivo</p><h2 class="text-2xl font-black tracking-tight text-white">{{ $empresa->currency_simbol }}{{ number_format($paymentTotals['cash'], 2) }}</h2></div>
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl border border-white/20 bg-white/15 text-lg text-white"><i class="fas fa-money-bill-wave"></i></div>
                </div>
            </div>
            @endif

            @if ($paymentTotals['yape'] > 0)
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-violet-500 to-purple-700 p-5 shadow-[0_8px_30px_rgb(139,92,246,0.3)] border border-white/20 transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_12px_40px_rgb(139,92,246,0.4)]">
                <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-white/10 blur-xl"></div>
                <div class="relative z-10 flex items-center justify-between">
                    <div class="space-y-1"><p class="text-[11px] font-bold uppercase tracking-widest text-violet-100">Yape</p><h2 class="text-2xl font-black tracking-tight text-white">{{ $empresa->currency_simbol }}{{ number_format($paymentTotals['yape'], 2) }}</h2></div>
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl border border-white/20 bg-white/15 text-lg text-white"><i class="fas fa-mobile-screen-button"></i></div>
                </div>
            </div>
            @endif

            @if ($paymentTotals['card'] > 0)
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-500 to-blue-700 p-5 shadow-[0_8px_30px_rgb(99,102,241,0.3)] border border-white/20 transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_12px_40px_rgb(99,102,241,0.4)]">
                <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-white/10 blur-xl"></div>
                <div class="relative z-10 flex items-center justify-between">
                    <div class="space-y-1"><p class="text-[11px] font-bold uppercase tracking-widest text-indigo-100">Tarjeta</p><h2 class="text-2xl font-black tracking-tight text-white">{{ $empresa->currency_simbol }}{{ number_format($paymentTotals['card'], 2) }}</h2></div>
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl border border-white/20 bg-white/15 text-lg text-white"><i class="fas fa-credit-card"></i></div>
                </div>
            </div>
            @endif
            </div>
        @endif
        <div class="bg-white border border-slate-200 rounded-3xl shadow-sm overflow-hidden">

            {{-- DESKTOP --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/50 border-b border-slate-100">
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase">Venta / Fecha</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase">Atención</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase">Mesero</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase text-right">Total</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase text-center">Acciones
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-50">
                        @forelse ($sales as $sale)
                            <tr class="hover:bg-slate-50/50 transition-colors group">

                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <span
                                            class="font-black text-slate-800 text-sm tracking-tighter">#{{ $sale->id }}</span>
                                        <span class="text-[10px] text-slate-400 font-bold uppercase">
                                             {{ $sale->paid_at->format('d/m/Y H:i') }}
                                        </span>
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <span
                                        class="bg-slate-100 text-slate-700 px-3 py-1 rounded-lg text-[10px] font-black uppercase">
                                         {{ $sale->order?->service_label ?? 'N/A' }}
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    <span class="text-xs font-bold text-slate-600 uppercase">
                                         {{ $sale->order?->user?->name ?? 'Sistema' }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 text-right">
                                    <div class="flex flex-col items-end leading-tight">
                                        <span class="text-sm font-black text-slate-900">
                                            {{ $empresa->currency_simbol }}{{ number_format($sale->total, 2) }}
                                        </span>

                                        @if ($sale->tip > 0)
                                            <span
                                                class="text-[10px] text-emerald-600 font-bold uppercase flex items-center gap-1">
                                                <i class="fas fa-hand-holding-heart"></i>
                                                Propina
                                                {{ $empresa->currency_simbol }}{{ number_format($sale->tip, 2) }}
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <td class="px-6 py-4 text-center">
                                    <div class="flex justify-center gap-2">
                                    <button wire:click="viewSale({{ $sale->id }})" class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-orange-50 text-orange-600 hover:bg-orange-600 hover:text-white" title="Ver detalle"><i class="fas fa-eye"></i></button>
                                    <a href="{{ route('sales.receipt', $sale->id) }}" target="_blank"
                                        class="inline-flex items-center justify-center h-9 w-9 rounded-xl bg-slate-100 text-slate-600 hover:bg-slate-800 hover:text-white transition-all shadow-sm">
                                        <i class="fas fa-print"></i>
                                    </a>
                                    </div>
                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                                    No se encontraron ventas registradas
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- MOBILE --}}
            <div class="md:hidden divide-y divide-slate-100">
                @foreach ($sales as $sale)
                    <div class="p-4 flex flex-col gap-3">

                        <div class="flex justify-between items-start">
                            <div class="flex flex-col">
                                <span class="font-black text-slate-800">Venta #{{ $sale->id }}</span>
                                <span class="text-[10px] text-slate-400">
                                     {{ $sale->paid_at->format('d M, Y h:i A') }}
                                </span>
                            </div>

                            <div class="text-right">
                                <div class="text-lg font-black text-orange-600">
                                    {{ $empresa->currency_simbol }}{{ number_format($sale->total, 2) }}
                                </div>

                                @if ($sale->tip > 0)
                                    <div class="text-[10px] text-emerald-600 font-bold uppercase">
                                        + Propina {{ $empresa->currency_simbol }}{{ number_format($sale->tip, 2) }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="flex items-center justify-between text-[10px] text-slate-500 uppercase">
                            <span>{{ $sale->order?->service_label ?? 'N/A' }}</span>
                            <span>{{ $sale->order?->user?->name ?? 'Sistema' }}</span>
                        </div>

                        <div class="flex gap-2">
                            <button wire:click="viewSale({{ $sale->id }})" class="rounded-lg bg-orange-600 px-4 py-1.5 text-[10px] font-black uppercase text-white"><i class="fas fa-eye"></i> Detalle</button>
                            <a href="{{ route('sales.receipt', $sale->id) }}" target="_blank" class="rounded-lg bg-slate-800 px-4 py-1.5 text-[10px] font-black uppercase text-white"><i class="fas fa-print"></i> Ticket</a>
                        </div>

                    </div>
                @endforeach
            </div>

            <div class="p-4 md:p-6 border-t border-slate-50 bg-slate-50/30">
                {{ $sales->links() }}
            </div>

        </div>

        @if ($selectedSale)
            <div class="fixed inset-0 z-[100] grid place-items-center p-4">
                <div class="absolute inset-0 bg-slate-950/50" wire:click="closeSaleDetails"></div>
                <section class="relative max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl">
                    <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
                        <div><p class="text-[10px] font-black uppercase tracking-[0.18em] text-orange-600">Auditoría de venta</p><h2 class="mt-1 text-xl font-black text-slate-900">Venta #{{ $selectedSale->id }}</h2><p class="mt-1 text-xs text-slate-500">{{ $selectedSale->paid_at->format('d/m/Y H:i') }} · {{ $selectedSale->customer_name ?: $selectedSale->order?->customer_name ?: 'Consumidor Final' }}</p></div>
                        <button wire:click="closeSaleDetails" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-3 text-xs sm:grid-cols-4"><div><p class="text-slate-400">Atención</p><p class="font-bold text-slate-700">{{ $selectedSale->order?->service_label ?? 'N/A' }}</p></div><div><p class="text-slate-400">Atendió</p><p class="font-bold text-slate-700">{{ $selectedSale->order?->user?->name ?? 'Sistema' }}</p></div><div><p class="text-slate-400">Caja</p><p class="font-bold text-slate-700">{{ $selectedSale->cashRegister?->name ?? 'N/A' }}</p></div><div><p class="text-slate-400">Estado</p><p class="font-bold text-emerald-600">Pagada</p></div></div>
                    <div class="mt-5 overflow-hidden rounded-xl border border-slate-200"><table class="w-full text-left text-xs"><thead class="bg-slate-50 text-[10px] uppercase text-slate-400"><tr><th class="px-4 py-3">Producto</th><th class="px-4 py-3 text-right">Cant.</th><th class="px-4 py-3 text-right">Total</th></tr></thead><tbody class="divide-y divide-slate-100">@foreach ($selectedSale->details as $detail)<tr><td class="px-4 py-3 font-semibold text-slate-700">{{ $detail->product_name ?: $detail->product?->name ?: 'Producto histórico' }}@if ($detail->notes)<p class="mt-1 text-[10px] font-normal text-slate-400">{{ $detail->notes }}</p>@endif</td><td class="px-4 py-3 text-right">{{ $detail->quantity }}</td><td class="px-4 py-3 text-right font-bold">{{ $empresa->currency_simbol }}{{ number_format($detail->subtotal + $detail->tax, 2) }}</td></tr>@endforeach</tbody></table></div>
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-xs">
                            <p class="font-bold text-slate-700">Pagos registrados</p>
                            @foreach ($selectedSale->payments as $payment)
                                <div class="mt-3 border-t border-slate-200 pt-3 first:mt-2 first:border-t-0 first:pt-0">
                                    <p class="flex justify-between font-semibold text-slate-600"><span>{{ $payment->method?->name ?? 'Método' }}</span><span>{{ $empresa->currency_simbol }}{{ number_format($payment->amount, 2) }}</span></p>
                                    @if ($payment->method?->is_efectivo && $payment->received_amount !== null)
                                        <p class="mt-1 flex justify-between text-slate-400"><span>Recibido</span><span>{{ $empresa->currency_simbol }}{{ number_format($payment->received_amount, 2) }}</span></p>
                                    @endif
                                    @if ($payment->method?->is_efectivo && $payment->returned_amount !== null)
                                        <p class="mt-1 flex justify-between text-slate-400"><span>Vuelto</span><span>{{ $empresa->currency_simbol }}{{ number_format($payment->returned_amount, 2) }}</span></p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        <div class="rounded-xl border border-orange-200 bg-gradient-to-br from-orange-50 to-amber-100 p-4 text-right text-xs shadow-sm">
                            <p class="font-bold uppercase tracking-wider text-orange-700">Total pagado</p>
                            <p class="mt-1 text-3xl font-black tracking-tight text-slate-800">{{ $empresa->currency_simbol }}{{ number_format($selectedSale->total, 2) }}</p>
                            <div class="mt-3 border-t border-orange-200 pt-3 text-slate-500">
                                <p class="flex justify-between"><span>Productos</span><span>{{ $empresa->currency_simbol }}{{ number_format($selectedSale->subtotal + $selectedSale->tax, 2) }}</span></p>
                                @if ($selectedSale->manual_discount > 0)<p class="mt-1 flex justify-between text-orange-700"><span>Descuento</span><span>-{{ $empresa->currency_simbol }}{{ number_format($selectedSale->manual_discount, 2) }}</span></p>@endif
                                @if ($selectedSale->tip > 0)<p class="mt-1 flex justify-between text-emerald-700"><span>Propina</span><span>{{ $empresa->currency_simbol }}{{ number_format($selectedSale->tip, 2) }}</span></p>@endif
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('sales.receipt', $selectedSale->id) }}" target="_blank" class="mt-5 inline-flex rounded-lg bg-orange-600 px-4 py-2 text-[10px] font-black uppercase text-white">Imprimir ticket</a>
                </section>
            </div>
        @endif
    </div>
</div>
