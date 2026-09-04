<div wire:poll.5s.keep-alive="refreshReadyOrderAlerts" class="p-6 bg-[#fcfcfc] min-h-screen font-sans text-slate-900 antialiased">
    <div class="mx-auto">

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-black tracking-tighter text-slate-800">MONITOR DE DESPACHO</h1>
                <p class="text-xs text-slate-400 font-bold uppercase tracking-widest">Control de platos por mesa</p>
            </div>

            <div class="flex items-center gap-3">
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
                            <span class="text-[10px] font-black text-orange-600 uppercase tracking-tighter">{{ $order->order_type_label }}</span>
                            <h2 class="text-2xl font-black leading-none text-slate-800">{{ $order->service_label }}</h2>
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

                    <div class="p-4 flex-1 space-y-5 max-h-[400px] overflow-y-auto custom-scrollbar">
                        @foreach ($order->details as $detail)
                            @php
                                $serviceStatus = $detail->service_status;
                            @endphp
                            @if (!$order->sale)
                            <div class="flex items-center gap-2 mt-1">
                                <label class="relative inline-flex items-center cursor-pointer scale-75 origin-left">
                                    <input type="checkbox" wire:model.live="selectedDetails.{{ $order->id }}.{{ $detail->id }}"
                                        class="sr-only peer">
                                    <div
                                        class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-orange-600">
                                    </div>
                                    <span
                                        class="ml-2 text-[11px] font-bold text-slate-400 peer-checked:text-orange-600 uppercase">Cobrar</span>
                                </label>
                            </div>
                            @endif
                            <div
                                class="group relative bg-white p-3 pt-5 rounded-2xl border border-slate-100 shadow-sm transition-all">
                                <div class="absolute -top-2.5 left-3">
                                    @if ($serviceStatus === 'in_progress')
                                        <span
                                            class="bg-cyan-500 text-white text-[8px] font-black px-2 py-0.5 rounded-md uppercase tracking-widest shadow-sm animate-pulse">En
                                            proceso</span>
                                    @elseif($serviceStatus === 'ready')
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
                                            @if (!empty($detail->selected_options))
                                                <p class="mt-1 text-[10px] font-black text-orange-600">
                                                    {{ collect($detail->selected_options)->map(fn ($option) => preg_replace('/^[^:]+:\s*/', '', $option['group']) . ': ' . $option['value'])->join(' · ') }}
                                                </p>
                                            @endif
                                            @if ($detail->notes)
                                                <p
                                                    class="text-[10px] text-orange-500 font-bold italic mt-1 leading-tight uppercase bg-orange-50/50 p-1 rounded-lg">
                                                    "{{ $detail->notes }}"
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="p-4 bg-slate-50/80 border-t border-slate-50 mt-auto">
                        <div class="flex justify-between items-center">
                            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter">
                                ID: #{{ $order->id }} | {{ $order->user->name ?? 'Sistema' }}
                            </span>
                            <span class="text-sm font-black text-slate-900">
                                {{ $empresa->currency_simbol }}{{ number_format($order->amount_pending, 2) }}
                            </span>
                        </div>
                        @if ($order->sale)
                            <p class="mt-3 border-t border-slate-200/60 pt-3 text-center text-[10px] font-black uppercase tracking-wider text-emerald-600">
                                Pagado · Pendiente de entrega
                            </p>
                        @elseif ($order->is_ready_for_checkout)
                            <div class="grid grid-cols-2 gap-2 mt-1 pt-2 border-t border-slate-200/60">
                                <button wire:click="openSplitPayment({{ $order->id }})"
                                    @if (count(array_filter($selectedDetails[$order->id] ?? [])) === 0) disabled @endif
                                    class="px-4 py-2 bg-white border border-orange-600 text-orange-600 text-xs font-black uppercase tracking-widest rounded-lg hover:bg-orange-50 active:scale-95 transition-all flex items-center justify-center gap-2 shadow-sm disabled:opacity-40 disabled:cursor-not-allowed">
                                    <i class="fas fa-list-check text-sm"></i>
                                    Separar ({{ count(array_filter($selectedDetails[$order->id] ?? [])) }})
                                </button>
                                <button wire:click="openFullPayment({{ $order->id }})"
                                    class="px-5 py-2 bg-orange-600 text-white text-xs font-black uppercase tracking-widest rounded-lg hover:bg-orange-700 shadow-md shadow-orange-100 active:scale-95 transition-all flex items-center justify-center gap-2">
                                    <i class="fas fa-cash-register text-sm"></i>
                                    Cobrar Total
                                </button>
                            </div>
                        @else
                            <div class="mt-3 border-t border-slate-200/60 pt-3">
                                <button wire:click="openFullPayment({{ $order->id }})"
                                    class="w-full px-5 py-2 bg-orange-600 text-white text-xs font-black uppercase tracking-widest rounded-lg hover:bg-orange-700 shadow-md shadow-orange-100 active:scale-95 transition-all flex items-center justify-center gap-2">
                                    <i class="fas fa-cash-register text-sm"></i>
                                    Cobrar adelantado
                                </button>
                            </div>
                        @endif
                    </div>

                    <div>
                        <button onclick="abrirVentanaEmergente('{{ route('orders.ticket', $order->id) }}')"
                            type="button"
                            class="w-full bg-slate-900 hover:bg-orange-600 text-white font-bold text-xs py-2 px-4 rounded-xl flex items-center justify-center gap-2 transition-all active:scale-[0.98] shadow-sm">
                            <i class="fa-solid fa-ticket text-[11px]"></i>
                            Ver Ticket
                        </button>
                    </div>
                </div>
            @empty
                <div
                    class="col-span-1 md:col-span-2 xl:col-span-2 2xl:col-span-3 bg-white border border-dashed border-slate-300 rounded-3xl p-12 text-center">
                    <div class="flex flex-col items-center justify-center gap-3 max-w-sm mx-auto">
                        <div
                            class="h-14 w-14 rounded-2xl bg-slate-50 text-slate-400 flex items-center justify-center text-2xl shadow-sm">
                            <i class="fa-solid fa-utensils"></i>
                        </div>
                        <h3 class="text-base font-black text-slate-800 uppercase tracking-tight">No hay cuentas activas
                        </h3>
                        <p class="text-xs text-slate-400 leading-normal">Actualmente no existen pedidos pendientes por
                            cobrar. Las nuevas comandas abiertas desde las mesas aparecerán en esta pantalla
                            automáticamente.</p>
                    </div>
                </div>
            @endforelse
        </div>

        <div class="mt-8">
            {{ $orders->links() }}
        </div>
    </div>

    @if ($showPaymentModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 transition-all">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" wire:click="$set('showPaymentModal', false)">
            </div>

            <div
                class="relative bg-white w-full max-w-2xl max-h-[95vh] flex flex-col rounded-2xl shadow-2xl overflow-hidden border border-slate-200">

                <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-white">
                    <div>
                        <h2 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">
                            {{ $quickCheckout ? 'Cobrar y liberar mesa' : 'Panel de Cobro' }}
                        </h2>
                        @if ($quickCheckout && $this->order?->table)
                            <p class="mt-1 text-xs font-black uppercase tracking-wide text-emerald-700">
                                Mesa {{ $this->order->table->name }} · servicio completo
                            </p>
                        @endif
                        <p class="text-3xl font-black text-slate-800 tracking-tight">
                            <span
                                class="text-orange-600 font-light">{{ $empresa->currency_simbol }}</span>{{ number_format($paymentAmount, 2) }}
                        </p>
                    </div>

                    <button wire:click="$set('showPaymentModal', false)"
                        class="p-2 hover:bg-slate-100 rounded-full text-slate-400 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto bg-slate-50/30 custom-scrollbar">

                    <div class="flex divide-x divide-slate-100 border-b border-slate-100 bg-white">

                        <div class="flex-1 p-4 text-center">
                            <span class="block text-[10px] uppercase font-bold text-slate-400">Recibido</span>
                            <span class="text-lg font-bold text-slate-700">
                                {{ $empresa->currency_simbol }}{{ number_format($this->paid, 2) }}
                            </span>
                        </div>

                        <div class="flex-1 p-4 text-center">
                            @if ($this->change > 0)
                                <span class="block text-[10px] uppercase font-bold text-orange-500">Vuelto</span>
                                <span class="text-lg font-bold text-orange-600">
                                    {{ $empresa->currency_simbol }}{{ number_format($this->change, 2) }}
                                </span>
                            @else
                                <span class="block text-[10px] uppercase font-bold text-slate-400">Faltante</span>
                                <span class="text-lg font-bold text-rose-500">
                                    {{ $empresa->currency_simbol }}{{ number_format(max(0, $paymentAmount - $this->paid), 2) }}
                                </span>
                            @endif
                        </div>

                    </div>

                    <div class="p-6">

                        <div class="flex items-end gap-2 bg-white p-4 rounded-xl border border-slate-200 shadow-sm">

                            <div class="flex-1">
                                    <label
                                        class="block text-[10px] font-black text-slate-400 uppercase mb-1.5 ml-1">Caja (solo efectivo)</label>
                                <select wire:model="boxId"
                                    class="w-full bg-slate-50 border-slate-200 rounded-lg py-1.5 px-3 text-sm">
                                    <option value="">Seleccionar...</option>
                                    @foreach ($boxes as $box)
                                        <option value="{{ $box->id }}">
                                            {{ $box->name }} ({{ $box->opener->name ?? 'Sistema' }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase mb-1.5 ml-1">
                                    Método de Pago
                                </label>

                                <select wire:model="selectedMethod"
                                    class="w-full bg-slate-50 border-slate-200 rounded-lg py-1.5 px-3 text-sm">
                                    <option value="">Seleccionar...</option>
                                    @foreach ($paymentMethods as $method)
                                        @php $used = collect($payments)->pluck('method_id')->contains($method->id); @endphp
                                        <option value="{{ $method->id }}"
                                            @if ($used) disabled @endif>
                                            {{ $method->name }} {{ $method->is_efectivo ? '(Efectivo)' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <button wire:click="addPaymentFromSelect"
                                class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg text-xs font-black uppercase tracking-widest transition-all active:scale-95 shadow-md shadow-orange-100">
                                Añadir
                            </button>

                        </div>

                        <div class="mt-4 grid grid-cols-3 gap-3">

                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase mb-1.5 ml-1">
                                    Subtotal
                                </label>
                                <div class="flex items-center bg-slate-100 rounded-md px-2 py-2">
                                    <span class="text-[10px] font-bold text-slate-400 mr-1">
                                        {{ $empresa->currency_simbol }}
                                    </span>
                                    <span class="text-sm font-black text-slate-700">
                                        {{ number_format($subtotal, 2) }}
                                    </span>
                                </div>
                            </div>

                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase mb-1.5 ml-1">
                                    Tax
                                </label>
                                <div class="flex items-center bg-slate-100 rounded-md px-2 py-2">
                                    <span class="text-[10px] font-bold text-slate-400 mr-1">
                                        {{ $empresa->currency_simbol }}
                                    </span>
                                    <span class="text-sm font-black text-slate-700">
                                        {{ number_format($tax, 2) }}
                                    </span>
                                </div>
                            </div>

                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase mb-1.5 ml-1">
                                    Propina
                                </label>
                                <div class="flex items-center bg-white border border-slate-200 rounded-md px-2 py-2">
                                    <span class="text-[10px] font-bold text-slate-400 mr-1">
                                        {{ $empresa->currency_simbol }}
                                    </span>
                                    <input type="number" step="0.01" min="0" wire:model.live="tip"
                                        class="w-full bg-transparent border-none p-0 text-sm font-black text-orange-600 focus:ring-0">
                                </div>
                            </div>

                        </div>

                        <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-[9rem_1fr]">
                            <div>
                                <label class="block text-[10px] font-black uppercase text-slate-400 mb-1.5 ml-1">Descuento manual</label>
                                <div class="flex items-center rounded-md border border-orange-200 bg-orange-50 px-2 py-2">
                                    <span class="mr-1 text-[10px] font-bold text-orange-400">{{ $empresa->currency_simbol }}</span>
                                    <input type="number" min="0" max="{{ $subtotal }}" step="0.01" wire:model.live="manual_discount"
                                        class="w-full border-none bg-transparent p-0 text-sm font-black text-orange-600 focus:ring-0">
                                </div>
                            </div>

                            <div>
                                <label class="block text-[10px] font-black uppercase text-slate-400 mb-1.5 ml-1">Motivo del descuento</label>
                                <input type="text" wire:model="manual_discount_reason" maxlength="255" placeholder="Ej.: cortesía, cliente frecuente"
                                    @disabled((float) $manual_discount <= 0)
                                    class="w-full rounded-md border-slate-200 px-3 py-2 text-sm disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400">
                            </div>
                        </div>

                        @if ($this->manualDiscount > 0)
                            <p class="mt-2 text-[10px] font-bold text-orange-600">
                                El descuento reduce {{ $empresa->currency_simbol }}{{ number_format($this->manualDiscount, 2) }} del subtotal y {{ $empresa->currency_simbol }}{{ number_format($this->manualDiscountTax, 2) }} del IGV.
                            </p>
                        @endif

                    </div>

                    <div class="px-6 pb-6">
                        <table class="w-full">

                            <thead>
                                <tr
                                    class="text-[9px] uppercase font-black text-slate-400 tracking-tighter border-b border-slate-100">
                                    <th class="pb-2 text-left px-2">Detalle</th>
                                    <th class="pb-2 text-right px-2">Monto</th>
                                    <th class="pb-2 text-center w-10"></th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-50">
                                @foreach ($payments as $index => $payment)
                                    @php $mInfo = collect($paymentMethods)->firstWhere('id', $payment['method_id']); @endphp

                                    <tr class="group">
                                        <td class="py-3 px-2">
                                            <div class="text-sm font-bold text-slate-700">{{ $mInfo->name ?? '-' }}
                                            </div>
                                            <input type="text"
                                                wire:model.live="payments.{{ $index }}.reference"
                                                placeholder="Referencia..."
                                                class="text-[10px] text-slate-400 bg-transparent border-none p-0 focus:ring-0 w-full h-4">
                                        </td>

                                        <td class="py-3 px-2 text-right">
                                            <div class="inline-flex items-center bg-slate-100 rounded-md px-2 py-1">
                                                <span class="text-[10px] font-bold text-slate-400 mr-1">
                                                    {{ $empresa->currency_simbol }}
                                                </span>
                                                <input type="number" step="0.01"
                                                    wire:model.live="payments.{{ $index }}.amount"
                                                    class="w-20 bg-transparent border-none p-0 text-right text-sm font-black text-orange-600 focus:ring-0">
                                            </div>
                                        </td>

                                        <td class="py-3 text-center">
                                            <button wire:click="removePaymentRow({{ $index }})"
                                                class="text-slate-300 hover:text-rose-500 transition-colors p-1">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>

                        </table>
                    </div>

                </div>

                <div class="p-6 bg-slate-50 border-t border-slate-200">

                    <button wire:click="processPayment" @if ($this->paid < $paymentAmount) disabled @endif
                        class="w-full flex items-center justify-center gap-2 bg-orange-600 disabled:bg-slate-300 text-white py-3 rounded-xl font-black text-sm uppercase tracking-widest transition-all shadow-lg shadow-orange-200 active:scale-[0.99]">

                        @if ($this->paid < $paymentAmount)
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            Faltan {{ $empresa->currency_simbol }}{{ number_format($paymentAmount - $this->paid, 2) }}
                        @else
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7"></path>
                            </svg>
                            {{ $quickCheckout ? 'Cobrar y liberar mesa' : 'Finalizar Operación' }}
                        @endif

                    </button>

                </div>

            </div>
        </div>
    @endif

    <div id="cashier-ready-toast" class="fixed right-4 top-4 z-[120] hidden max-w-sm rounded-2xl border border-emerald-200 bg-white p-4 shadow-xl" role="status" aria-live="polite">
        <div class="flex items-start gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600">
                <i class="fa-solid fa-cash-register"></i>
            </span>
            <div>
                <p class="text-sm font-black text-slate-800">Pedido listo para cobrar</p>
                <p class="text-xs font-semibold text-slate-500" data-cashier-ready-message></p>
            </div>
        </div>
    </div>
</div>

<x-cashier-bell-listeners />

<script>
    document.addEventListener('livewire:init', function() {
        Livewire.on('print-receipt', (event) => {
            Swal.fire({
                title: '¿Desea imprimir el recibo de este pedido?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, imprimir',
                cancelButtonText: 'No, gracias',
            }).then((result) => {
                if (result.isConfirmed) {
                    const res = event[0];
                    printKitchenTicket(res.url, res.printer_name);
                    @this.set('showPaymentModal', false);
                }else{
                    @this.set('showPaymentModal', false);
                }

            });
        });
    });

    function printKitchenTicket(url, printer_name) {

        @if (!$empresa->direct_printing)
            abrirVentanaEmergente(url);
            return;
        @endif

        fetch("{{ env('IMPRESION_LOCAL_URL') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    venta_url: url,
                    printer: printer_name
                })
            })
            .then(r => r.json())
            .then(res => {
                Swal.close();

                if (!res.success) {
                    Swal.fire({
                        title: "Error de impresión",
                        text: res.message ||
                            "Ocurrió un error al intentar imprimir. Por favor, verifica la configuración de tu impresora.",
                        icon: "error",
                        toast: true,
                        timer: 3000,
                        position: 'top-end',
                        showConfirmButton: false,
                        customClass: {
                            confirmButton: 'rounded-xl px-6 py-2.5 text-xs font-bold uppercase tracking-widest'
                        }
                    });

                    return;
                }

                Swal.fire({
                    title: "Impresión exitosa",
                    text: "El ticket se ha enviado a la impresora correctamente.",
                    icon: "success",
                    toast: true,
                    timer: 2000,
                    position: 'top-end',
                    showConfirmButton: false,
                    customClass: {
                        confirmButton: 'rounded-xl px-6 py-2.5 text-xs font-bold uppercase tracking-widest'
                    }
                });
            })
            .catch(err => {
                Swal.close();

                Swal.fire({
                    title: "Error de impresión",
                    text: "No se pudo conectar con el servicio de impresión local. Por favor, verifica que esté en ejecución.",
                    icon: "error",
                    toast: true,
                    timer: 3000,
                    position: 'top-end',
                    showConfirmButton: false,
                    customClass: {
                        confirmButton: 'rounded-xl px-6 py-2.5 text-xs font-bold uppercase tracking-widest'
                    }
                });
            });

    }
</script>
