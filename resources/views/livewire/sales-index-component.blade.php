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

                    <div class="flex-1 min-w-[180px]">
                        <label class="text-[10px] font-black text-slate-400 uppercase ml-1 mb-1 block">Mesero</label>
                        <x-searchable-select model="waiter" :options="$waiters" placeholder="Todos los meseros" icon="fa-user" />
                    </div>

                    <div class="flex-1 min-w-[180px]">
                        <label class="text-[10px] font-black text-slate-400 uppercase ml-1 mb-1 block">Cliente</label>
                        <x-searchable-select model="customer" :options="$customers" placeholder="Todos los clientes" icon="fa-user-tag" />
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

        <div class="mb-6 grid gap-4 sm:grid-cols-2 {{ $tipsEnabled ? 'xl:grid-cols-5' : 'xl:grid-cols-4' }}">
            <section class="relative overflow-hidden rounded-2xl border border-white/20 bg-gradient-to-br from-cyan-500 to-blue-600 p-5 text-white shadow-[0_8px_30px_rgb(6,182,212,0.25)]"><i class="fa-solid fa-cash-register absolute -right-3 -top-4 text-8xl text-white/10"></i><p class="relative text-[10px] font-black uppercase tracking-widest text-cyan-100">Total ventas</p><p class="relative mt-2 text-3xl font-black tracking-tight">{{ $empresa->currency_simbol }}{{ number_format($totalSales, 2) }}</p></section>
            @if ($tipsEnabled)
                <section class="relative overflow-hidden rounded-2xl border border-white/20 bg-gradient-to-br from-emerald-500 to-teal-600 p-5 text-white shadow-[0_8px_30px_rgb(16,185,129,0.25)]"><i class="fa-solid fa-hand-holding-heart absolute -right-3 -top-4 text-8xl text-white/10"></i><p class="relative text-[10px] font-black uppercase tracking-widest text-emerald-100">Total propinas</p><p class="relative mt-2 text-3xl font-black tracking-tight">{{ $empresa->currency_simbol }}{{ number_format($totalTips, 2) }}</p></section>
            @endif
            @if ($paymentTotals['cash'] > 0)
                <section class="relative overflow-hidden rounded-2xl border border-white/20 bg-gradient-to-br from-orange-500 to-amber-600 p-5 text-white shadow-[0_8px_30px_rgb(249,115,22,0.25)]"><i class="fa-solid fa-money-bill-wave absolute -right-3 -top-4 text-8xl text-white/10"></i><p class="relative text-[10px] font-black uppercase tracking-widest text-orange-100">Efectivo</p><p class="relative mt-2 text-3xl font-black tracking-tight">{{ $empresa->currency_simbol }}{{ number_format($paymentTotals['cash'], 2) }}</p></section>
            @endif
            @if ($paymentTotals['yape'] > 0)
                <section class="relative overflow-hidden rounded-2xl border border-white/20 bg-gradient-to-br from-violet-500 to-purple-700 p-5 text-white shadow-[0_8px_30px_rgb(139,92,246,0.25)]"><i class="fa-solid fa-mobile-screen-button absolute -right-3 -top-4 text-8xl text-white/10"></i><p class="relative text-[10px] font-black uppercase tracking-widest text-violet-100">Yape</p><p class="relative mt-2 text-3xl font-black tracking-tight">{{ $empresa->currency_simbol }}{{ number_format($paymentTotals['yape'], 2) }}</p></section>
            @endif
            @if ($paymentTotals['card'] > 0)
                <section class="relative overflow-hidden rounded-2xl border border-white/20 bg-gradient-to-br from-slate-700 to-slate-900 p-5 text-white shadow-[0_8px_30px_rgb(15,23,42,0.2)]"><i class="fa-solid fa-credit-card absolute -right-3 -top-4 text-8xl text-white/10"></i><p class="relative text-[10px] font-black uppercase tracking-widest text-slate-300">Tarjeta</p><p class="relative mt-2 text-3xl font-black tracking-tight">{{ $empresa->currency_simbol }}{{ number_format($paymentTotals['card'], 2) }}</p></section>
            @endif
        </div>
        <div class="bg-white border border-slate-200 rounded-3xl shadow-sm overflow-hidden">

            {{-- DESKTOP --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/50 border-b border-slate-100">
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase">Venta / Fecha</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase">Tipo de atención</th>
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
                                            {{ $empresa->currency_simbol }}{{ number_format($tipsEnabled ? $sale->adjusted_total : $sale->total, 2) }}
                                        </span>

                                        @if ($tipsEnabled && $sale->adjusted_tip > 0)
                                            <span
                                                class="text-[10px] text-emerald-600 font-bold uppercase flex items-center gap-1">
                                                <i class="fas fa-hand-holding-heart"></i>
                                                Propina
                                                {{ $empresa->currency_simbol }}{{ number_format($sale->adjusted_tip, 2) }}
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
                                    {{ $empresa->currency_simbol }}{{ number_format($tipsEnabled ? $sale->adjusted_total : $sale->total, 2) }}
                                </div>

                                @if ($tipsEnabled && $sale->adjusted_tip > 0)
                                    <div class="text-[10px] text-emerald-600 font-bold uppercase">
                                        + Propina {{ $empresa->currency_simbol }}{{ number_format($sale->adjusted_tip, 2) }}
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
                            <div class="flex items-center justify-between gap-3"><p class="font-bold text-slate-700">Pagos registrados</p>@can('ordenes.cobrar')@if ($canEditPayments)<button wire:click="openPaymentEditor({{ $selectedSale->id }})" class="rounded-lg bg-white px-2 py-1 text-[10px] font-black uppercase text-violet-700 shadow-sm ring-1 ring-violet-200 hover:bg-violet-50">Corregir</button>@endif@endcan</div>
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
                            <p class="mt-1 text-3xl font-black tracking-tight text-slate-800">{{ $empresa->currency_simbol }}{{ number_format($tipsEnabled ? $selectedSale->adjusted_total : $selectedSale->total, 2) }}</p>
                            <div class="mt-3 border-t border-orange-200 pt-3 text-slate-500">
                                <p class="flex justify-between"><span>Productos</span><span>{{ $empresa->currency_simbol }}{{ number_format($selectedSale->subtotal + $selectedSale->tax, 2) }}</span></p>
                                @if ($selectedSale->manual_discount > 0)<p class="mt-1 flex justify-between text-orange-700"><span>Descuento</span><span>-{{ $empresa->currency_simbol }}{{ number_format($selectedSale->manual_discount, 2) }}</span></p>@endif
                                @if ($tipsEnabled && $selectedSale->adjusted_tip > 0)<p class="mt-1 flex justify-between text-emerald-700"><span>Propina</span><span>{{ $empresa->currency_simbol }}{{ number_format($selectedSale->adjusted_tip, 2) }}</span></p>@endif
                            </div>
                        </div>
                    </div>
                    @if ($tipsEnabled)
                        <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-xs">
                            <div class="flex items-center justify-between gap-3"><div><p class="font-bold text-emerald-800">Ajustes de propina</p><p class="mt-1 text-emerald-700">La venta original no se modifica.</p></div>@can('empresa.editar')<button wire:click="openTipAdjustment({{ $selectedSale->id }})" class="rounded-lg bg-emerald-600 px-3 py-2 text-[10px] font-black uppercase text-white">Ajustar</button>@endcan</div>
                            @forelse ($selectedSale->tipAdjustments as $adjustment)
                                <div class="mt-3 border-t border-emerald-200 pt-3"><p class="flex justify-between font-semibold text-emerald-900"><span>{{ $adjustment->amount >= 0 ? '+' : '-' }}{{ $empresa->currency_simbol }}{{ number_format(abs($adjustment->amount), 2) }}</span><span>{{ $adjustment->adjusted_at->format('d/m/Y H:i') }}</span></p><p class="mt-1 text-emerald-700">{{ $adjustment->reason }} · {{ $adjustment->paymentMethod?->name ?? 'Método histórico' }} · {{ $adjustment->adjuster?->name ?? 'Usuario histórico' }}</p></div>
                            @empty
                                <p class="mt-3 border-t border-emerald-200 pt-3 text-emerald-700">Sin ajustes posteriores.</p>
                            @endforelse
                        </div>
                    @endif
                    @if ($showTipAdjustment)
                        <div class="mt-5 rounded-xl border border-orange-200 bg-orange-50 p-4 text-xs">
                            <div class="flex items-start justify-between gap-3"><div><p class="font-bold text-orange-800">Ajustar propina</p><p class="mt-1 text-orange-700">Usa un importe positivo para agregar o negativo para devolver.</p></div><button wire:click="closeTipAdjustment" class="text-orange-600"><i class="fa-solid fa-xmark"></i></button></div>
                            <div class="mt-4 grid gap-3 sm:grid-cols-2"><div><label class="font-semibold text-slate-600">Importe</label><input wire:model="tipAdjustmentAmount" type="number" step="0.01" placeholder="Ej. 5.00 o -5.00" class="mt-1 w-full rounded-lg border-slate-200 text-sm"><x-input-error :messages="$errors->get('tipAdjustmentAmount')" /></div><div><label class="font-semibold text-slate-600">Medio de pago</label><select wire:model="tipAdjustmentPaymentMethodId" class="mt-1 w-full rounded-lg border-slate-200 text-sm"><option value="">Selecciona</option>@foreach ($paymentMethods as $method)<option value="{{ $method->id }}">{{ $method->name }}</option>@endforeach</select><x-input-error :messages="$errors->get('tipAdjustmentPaymentMethodId')" /></div><div class="sm:col-span-2"><label class="font-semibold text-slate-600">Caja abierta</label><select wire:model="tipAdjustmentCashRegisterId" class="mt-1 w-full rounded-lg border-slate-200 text-sm"><option value="">Selecciona</option>@foreach ($cashRegisters as $cashRegister)<option value="{{ $cashRegister->id }}">{{ $cashRegister->name }} · {{ $empresa->currency_simbol }}{{ number_format($cashRegister->current_amount, 2) }}</option>@endforeach</select><x-input-error :messages="$errors->get('tipAdjustmentCashRegisterId')" /></div><div class="sm:col-span-2"><label class="font-semibold text-slate-600">Motivo</label><input wire:model="tipAdjustmentReason" maxlength="255" class="mt-1 w-full rounded-lg border-slate-200 text-sm"><x-input-error :messages="$errors->get('tipAdjustmentReason')" /></div><div class="sm:col-span-2"><label class="font-semibold text-slate-600">Referencia opcional</label><input wire:model="tipAdjustmentReference" maxlength="255" class="mt-1 w-full rounded-lg border-slate-200 text-sm"></div></div>
                            <button wire:click="saveTipAdjustment" class="mt-4 rounded-lg bg-orange-600 px-4 py-2 text-[10px] font-black uppercase text-white">Registrar ajuste</button>
                        </div>
                    @endif
                    @if ($showPaymentEditor)
                        <div class="mt-5 rounded-xl border border-violet-200 bg-violet-50 p-4 text-xs">
                            <div class="flex items-start justify-between gap-3"><div><p class="font-bold text-violet-900">Corregir pagos</p><p class="mt-1 text-violet-700">La suma debe mantenerse igual y el cambio queda auditado.</p></div><button wire:click="closePaymentEditor" class="text-violet-600"><i class="fa-solid fa-xmark"></i></button></div>
                            <div class="mt-4 space-y-3">
                                @foreach ($paymentEdits as $index => $payment)
                                    <div wire:key="payment-edit-{{ $payment['id'] }}" class="grid gap-3 rounded-lg border border-violet-100 bg-white p-3 sm:grid-cols-3">
                                        <select wire:model="paymentEdits.{{ $index }}.payment_method_id" class="rounded-lg border-slate-200 text-sm"><option value="">Método</option>@foreach ($paymentMethods as $method)<option value="{{ $method->id }}">{{ $method->name }}</option>@endforeach</select>
                                        <input wire:model="paymentEdits.{{ $index }}.amount" type="number" min="0.01" step="0.01" placeholder="Monto" class="rounded-lg border-slate-200 text-sm">
                                        <input wire:model="paymentEdits.{{ $index }}.reference" maxlength="255" placeholder="Referencia" class="rounded-lg border-slate-200 text-sm">
                                    </div>
                                @endforeach
                            </div>
                            <label class="mt-3 block font-semibold text-slate-600">Motivo<input wire:model="paymentEditReason" maxlength="255" placeholder="Ej. método ingresado por error" class="mt-1 w-full rounded-lg border-slate-200 text-sm"></label>
                            @error('paymentEditReason')<p class="mt-1 text-rose-600">{{ $message }}</p>@enderror
                            <button wire:click="savePaymentEdits" class="mt-4 rounded-lg bg-violet-600 px-4 py-2 text-[10px] font-black uppercase text-white hover:bg-violet-700">Guardar corrección</button>
                        </div>
                    @endif
                    <a href="{{ route('sales.receipt', $selectedSale->id) }}" target="_blank" class="mt-5 inline-flex rounded-lg bg-orange-600 px-4 py-2 text-[10px] font-black uppercase text-white">Imprimir ticket</a>
                </section>
            </div>
        @endif
    </div>
</div>
