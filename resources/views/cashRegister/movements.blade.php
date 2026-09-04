<x-admin-layout>
    <div class="mx-auto animate-fade-in text-slate-700 relative overflow-hidden">

        {{-- MARCA DE AGUA (Solo visible si la caja está cerrada) --}}
        @if ($caja->status == 'closed' || $caja->status == 'cerrado')
            <div
                class="absolute inset-0 flex items-center justify-center pointer-events-none z-0 opacity-[0.03] rotate-[-12deg]">
                <h2 class="text-[12rem] font-black uppercase tracking-tighter">Cerrada</h2>
            </div>
        @endif

        <div class="relative z-10 space-y-6">
            {{-- HEADER COMPACTO --}}
            <div
                class="flex flex-col sm:flex-row justify-between items-center gap-4 bg-white/80 backdrop-blur-md p-5 rounded-2xl border border-slate-200 shadow-sm">
                <div class="flex items-center gap-4">
                    <div
                        class="w-12 h-12 {{ $caja->status == 'open' || $caja->status == 'abierto' ? 'bg-indigo-600' : 'bg-slate-400' }} rounded-xl flex items-center justify-center shadow-md">
                        <i class="fa-solid fa-vault text-xl text-white"></i>
                    </div>
                    <div>
                        <a href="{{ route('boxes.index') }}"
                            class="text-indigo-600 text-[10px] font-bold uppercase tracking-wider hover:underline flex items-center gap-1">
                            <i class="fa-solid fa-chevron-left text-[8px]"></i> Volver
                        </a>
                        <h1 class="text-xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
                            {{ $caja->terminal?->name ?? $caja->name }}
                            @if ($caja->status == 'closed' || $caja->status == 'cerrado')
                                <span
                                    class="bg-slate-100 text-slate-500 text-[10px] px-2 py-0.5 rounded-full uppercase italic">Cerrada</span>
                            @endif
                        </h1>
                        <p class="text-slate-500 text-xs">Usuario: <span
                                class="font-semibold">{{ $caja->opener->name }}</span></p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('boxes.movements', [$id, 'action' => 'pdf']) }}" target="_blank"
                        class="bg-slate-900 hover:bg-slate-800 text-white px-4 py-2 rounded-lg text-xs font-bold transition-all flex items-center gap-2 shadow-sm">
                        <i class="fa-solid fa-file-pdf"></i> REPORTE
                    </a>
                </div>
            </div>

            {{-- GRID DE MÉTODOS --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach ($pagosPorMetodo as $metodo => $total)
                    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-8 h-8 bg-indigo-50 rounded-lg flex items-center justify-center">
                                <i class="fa-solid fa-wallet text-indigo-500 text-xs"></i>
                            </div>
                            <p class="text-[11px] font-bold text-slate-500 uppercase tracking-tight">{{ $metodo }}
                            </p>
                        </div>
                        <p class="text-xl font-bold text-slate-900">
                            <span
                                class="text-slate-400 text-sm font-medium mr-0.5">{{ $empresa->currency_simbol }}</span>{{ number_format($total, 2) }}
                        </p>
                    </div>
                @endforeach
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- TABLA CRONOLÓGICA --}}
                <div class="lg:col-span-2">
                    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                        <div
                            class="px-5 py-4 flex justify-between items-center border-b border-slate-100 bg-slate-50/50">
                            <h3 class="text-slate-800 font-bold text-xs uppercase">Cronología</h3>
                            <span
                                class="bg-indigo-100 text-indigo-700 text-[10px] font-bold px-2 py-0.5 rounded-md italic">
                                {{ $cashSales->count() + $gastos->count() }} ops
                            </span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr
                                        class="text-[10px] text-slate-400 uppercase font-bold border-b border-slate-100 italic">
                                        <th class="px-5 py-3">Fecha</th>
                                        <th class="px-5 py-3">Concepto</th>
                                        <th class="px-5 py-3 text-right">Monto</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50">
                                    @php
                                        $movimientos = collect()
                                            ->merge($cashSales->map(function($sale) {
                                                $cashPayments = $sale->payments->filter(fn($p) => $p->method?->is_efectivo);

                                                return [
                                                    'date' => $sale->paid_at,
                                                    'type' => 'ingreso',
                                                    'concept' => 'Venta realizada',
                                                    'methods' => $cashPayments->map(fn($p) => $p->method->name)->implode(', '),
                                                    'amount' => $cashPayments->sum('amount')
                                                ];
                                            }))
                                            ->merge($gastos->map(function($gasto) {
                                                return [
                                                    'date' => $gasto->expense_date,
                                                    'type' => 'egreso',
                                                    'concept' => $gasto->concept . ($gasto->description ? ' - ' . $gasto->description : ''),
                                                    'methods' => $gasto->paymentMethod->name ?? 'N/A',
                                                    'amount' => $gasto->amount
                                                ];
                                            }))
                                            ->sortByDesc('date');
                                    @endphp

                                    @foreach ($movimientos as $m)
                                        <tr class="hover:bg-slate-50/50 transition-colors">
                                            <td class="px-5 py-3 text-xs text-slate-500 font-medium">
                                                {{ $m['date']->format('d/m/Y h:i A') }}
                                            </td>
                                            <td class="px-5 py-3 text-xs">
                                                <div class="font-semibold text-slate-800">{{ $m['concept'] }}</div>
                                                <div class="text-[10px] text-indigo-500 font-medium uppercase tracking-tighter">
                                                    {{ $m['methods'] }}
                                                </div>
                                            </td>
                                            <td class="px-5 py-3 text-right">
                                                @if($m['type'] == 'ingreso')
                                                    <span class="text-sm font-bold text-emerald-600">
                                                        +{{ number_format($m['amount'], 2) }}
                                                    </span>
                                                @else
                                                    <span class="text-sm font-bold text-rose-600">
                                                        -{{ number_format($m['amount'], 2) }}
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- PANEL DE ESTADO --}}
                <div class="lg:col-span-1 space-y-4">
                    <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
                        <h4
                            class="text-slate-900 text-xs font-bold uppercase tracking-wider mb-6 border-b border-slate-100 pb-3">
                            Resumen de Liquidación
                        </h4>

                        <div class="space-y-4">
                            <div class="flex justify-between items-center text-sm italic">
                                <span class="text-slate-500">Monto Inicial</span>
                                <span
                                    class="font-semibold text-slate-700">{{ $empresa->currency_simbol }}{{ number_format($caja->opening_amount, 2) }}</span>
                            </div>

                            <div class="flex justify-between items-center text-sm italic text-rose-600">
                                <span>Total Egresos (Gastos)</span>
                                <span class="font-semibold">-{{ $empresa->currency_simbol }}{{ number_format($gastos->sum('amount'), 2) }}</span>
                            </div>

                            @if ($caja->status === 'closed' && $caja->closing_amount !== null)
                                <div class="flex justify-between items-center text-sm italic">
                                    <span class="text-slate-500">Efectivo contado</span>
                                    <span class="font-semibold text-slate-700">{{ $empresa->currency_simbol }}{{ number_format($caja->closing_amount, 2) }}</span>
                                </div>
                                <div class="flex justify-between items-center text-sm italic {{ $caja->difference == 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                    <span>Diferencia</span>
                                    <span class="font-semibold">{{ $empresa->currency_simbol }}{{ number_format($caja->difference, 2) }}</span>
                                </div>
                            @endif

                            <div class="mt-6 pt-6 border-t border-slate-100">
                                <p class="text-indigo-600 text-[10px] font-bold uppercase mb-1">Efectivo Total en Caja</p>
                                <p class="text-4xl font-bold text-slate-900 tracking-tight">
                                    <span
                                        class="text-lg font-medium text-slate-400 mr-1">{{ $empresa->currency_simbol }}</span>{{ number_format($caja->current_amount, 2) }}
                                </p>
                            </div>
                        </div>

                        @if ($caja->status == 'open' || $caja->status == 'abierto')
                            @can('cajas.cerrar')
                                <button onclick="confirmarCierreCaja('{{ $caja->id }}')"
                                    class="w-full mt-6 bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white transition-all py-3 rounded-lg font-bold text-xs flex items-center justify-center gap-2">
                                    <i class="fa-solid fa-lock"></i>
                                    CERRAR CAJA
                                </button>
                            @endcan
                        @else
                            <div
                                class="w-full mt-6 bg-slate-50 text-slate-400 py-3 rounded-lg font-bold text-xs flex items-center justify-center gap-2 border border-dashed border-slate-200">
                                <i class="fa-solid fa-check-double"></i>
                                CAJA FINALIZADA
                            </div>
                        @endif
                    </div>

                    @if($gastos->count() > 0)
                        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                            <h4 class="text-slate-900 text-xs font-bold uppercase tracking-wider mb-4 border-b border-slate-100 pb-2 flex justify-between">
                                <span>Detalle Egresos</span>
                                <span class="text-rose-600 font-bold">{{ $gastos->count() }}</span>
                            </h4>
                            <div class="max-h-60 overflow-y-auto divide-y divide-slate-100 space-y-2 pr-1">
                                @foreach($gastos as $g)
                                    <div class="pt-2 flex justify-between items-start text-xs">
                                        <div>
                                            <p class="font-semibold text-slate-800">{{ $g->concept }}</p>
                                            <p class="text-[10px] text-slate-400">{{ $g->paymentMethod->name ?? 'N/A' }}</p>
                                        </div>
                                        <span class="font-bold text-rose-600">
                                            -{{ number_format($g->amount, 2) }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        function confirmarCierreCaja(cajaId) {
            Swal.fire({
                title: '¿Cerrar caja?',
                text: "Registra el efectivo contado para cerrar la sesión.",
                icon: 'warning',
                input: 'number',
                inputLabel: 'Efectivo contado',
                inputValue: '{{ $caja->current_amount }}',
                inputAttributes: { min: '0', step: '0.01' },
                showCancelButton: true,
                confirmButtonColor: '#4f46e5',
                cancelButtonColor: '#f43f5e',
                confirmButtonText: 'Sí, cerrar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true,
                inputValidator: (value) => !value && value !== '0' ? 'Ingresa el efectivo contado.' : undefined,
            }).then((result) => {
                if (result.isConfirmed) {
                    cerrarCajaFetch(cajaId, result.value);
                }
            });
        }

        function cerrarCajaFetch(cajaId, countedAmount) {
            Swal.fire({
                title: 'Procesando...',
                didOpen: () => Swal.showLoading(),
                allowOutsideClick: false
            });
            fetch('{{ route('boxes.close', $caja) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        counted_amount: countedAmount
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                                title: '¡Cerrada!',
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false
                            })
                            .then(() => window.location
                        .reload());
                    } else {
                        throw new Error(data.message);
                    }
                })
                .catch(error => Swal.fire('Error', error.message, 'error'));
        }
    </script>
</x-admin-layout>
