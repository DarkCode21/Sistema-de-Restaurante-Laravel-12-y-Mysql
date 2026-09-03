<x-admin-layout>
    <style>
        /* Gradientes sutiles y reflejos de luz estilo Material Design */
        .premium-gradient-1 {
            bg-gradient-to-br from-amber-500 via-orange-600 to-red-600
        }

        .ultra-card {
            background: #ffffff;
            position: relative;
            overflow: hidden;
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 10px 30px -5px rgba(15, 23, 42, 0.04), 0 4px 12px -2px rgba(15, 23, 42, 0.02);
        }

        .ultra-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: transparent;
            transition: all 0.35s ease;
        }

        .card-ventas::before {
            background: linear-gradient(90deg, #f97316, #ea580c);
        }

        .card-mesas::before {
            background: linear-gradient(90deg, #f59e0b, #d97706);
        }

        .card-cocina::before {
            background: linear-gradient(90deg, #6366f1, #4f46e5);
        }

        .card-ticket::before {
            background: linear-gradient(90deg, #10b981, #059669);
        }

        .ultra-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px -5px rgba(15, 23, 42, 0.08), 0 8px 20px -4px rgba(15, 23, 42, 0.04);
        }

        .ultra-card:hover .icon-container {
            transform: scale(1.1) rotate(4deg);
        }

        .pulse-orange {
            box-shadow: 0 0 0 0 rgba(249, 115, 22, 0.4);
            animation: pulse-orange 2s infinite;
        }

        @keyframes pulse-orange {
            70% {
                box-shadow: 0 0 0 10px rgba(249, 115, 22, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(249, 115, 22, 0);
            }
        }
    </style>

    <div class="min-h-screen antialiased font-sans">

        <div class="flex flex-col md:flex-row md:items-center justify-between pb-6 border-b border-slate-200/70">
            <div>
                <h1
                    class="text-3xl font-black tracking-tight text-slate-900 bg-clip-text text-transparent bg-gradient-to-r from-slate-900 via-orange-950 to-slate-900">
                    Resumen Diario
                </h1>
                <p class="text-xs text-slate-500 font-semibold mt-1.5 flex items-center gap-2">
                    <span class="relative flex h-2.5 w-2.5">
                        <span
                            class="animate-ping absolute inline-flex h-full w-full rounded-full bg-orange-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-orange-500"></span>
                    </span>
                    <span class="font-bold text-slate-700 tracking-wide">{{ config('app.name', 'CevicheFlow') }}</span>
                    <span class="text-slate-300">|</span>
                    <span class="text-slate-400 font-medium bg-slate-100 px-2 py-0.5 rounded-md">Terminal de Control
                        Activa</span>
                </p>
            </div>
            <div class="mt-4 md:mt-0">
                <a href="{{ route('tables.index') }}"
                    class="pulse-orange flex items-center px-6 py-3 bg-gradient-to-r from-orange-600 via-orange-500 to-amber-500 hover:from-orange-700 hover:to-amber-600 rounded-2xl text-xs font-bold text-white shadow-lg shadow-orange-500/20 transition-all active:scale-95 tracking-wider uppercase">
                    <i class="fa-solid fa-plus mr-2.5 text-xs animate-bounce"></i> Nueva Orden
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

            <div class="ultra-card card-ventas p-6 rounded-2xl">
                <div class="flex items-center justify-between mb-5">
                    <div
                        class="icon-container w-12 h-12 bg-gradient-to-br from-orange-500 to-red-500 rounded-xl flex items-center justify-center text-white shadow-md shadow-orange-500/20 transition-all duration-300">
                        <i class="fa-solid fa-dollar-sign text-xl"></i>
                    </div>
                    <span
                        class="text-[11px] font-black {{ $variacionVentas >= 0 ? 'text-emerald-700 bg-emerald-50 border-emerald-200' : 'text-rose-700 bg-rose-50 border-rose-200' }} px-2.5 py-1 rounded-lg border shadow-sm">
                        {{ $variacionVentas >= 0 ? '▲' : '▼' }} {{ number_format(abs($variacionVentas), 1) }}%
                    </span>
                </div>
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">Ventas del Día</p>
                <h3 class="text-2xl font-black text-slate-800 tracking-tight mt-1">
                    <span
                        class="text-orange-600 font-bold mr-0.5">{{ $empresa->currency_simbol }}</span>{{ number_format($ventasHoy, 2) }}
                </h3>
            </div>

            <div class="ultra-card card-mesas p-6 rounded-2xl">
                <div class="flex items-center justify-between mb-5">
                    <div
                        class="icon-container w-12 h-12 bg-gradient-to-br from-amber-400 to-orange-500 rounded-xl flex items-center justify-center text-white shadow-md shadow-amber-500/20 transition-all duration-300">
                        <i class="fa-solid fa-utensils text-xl"></i>
                    </div>
                    <div class="flex flex-col text-right">
                        <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">Ocupación</span>
                        <span
                            class="text-xs font-black text-amber-600 bg-amber-50 px-2 py-0.5 rounded border border-amber-100 mt-0.5">{{ number_format($porcentajeOcupacion, 0) }}%</span>
                    </div>
                </div>
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">Mesas Activas</p>
                <h3 class="text-2xl font-black text-slate-800 tracking-tight mt-1">
                    {{ $mesasOcupadas }} <span class="text-slate-300 font-normal text-lg">/ {{ $mesasTotales }}</span>
                </h3>
            </div>

            <div class="ultra-card card-cocina p-6 rounded-2xl">
                <div class="flex items-center justify-between mb-5">
                    <div
                        class="icon-container w-12 h-12 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center text-white shadow-md shadow-indigo-500/20 transition-all duration-300">
                        <i class="fa-solid fa-fire-burner text-xl"></i>
                    </div>
                    @if ($comandasEnCocina > 0)
                        <span
                            class="animate-pulse inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-black bg-gradient-to-r from-indigo-500 to-purple-500 text-white shadow-sm border border-indigo-400 uppercase tracking-wider">
                            Producción
                        </span>
                    @endif
                </div>
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">Pendiente Cocina</p>
                <h3 class="text-2xl font-black text-slate-800 tracking-tight mt-1">
                    {{ str_pad($comandasEnCocina, 2, '0', STR_PAD_LEFT) }} <span
                        class="text-xs font-bold text-indigo-500 uppercase tracking-wider ml-1">Comandas</span>
                </h3>
            </div>

            <div class="ultra-card card-ticket p-6 rounded-2xl">
                <div class="flex items-center justify-between mb-5">
                    <div
                        class="icon-container w-12 h-12 bg-gradient-to-br from-emerald-400 to-teal-600 rounded-xl flex items-center justify-center text-white shadow-md shadow-emerald-500/20 transition-all duration-300">
                        <i class="fa-solid fa-receipt text-xl"></i>
                    </div>
                </div>
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">Total Propinas</p>
                <h3 class="text-2xl font-black text-slate-800 tracking-tight mt-1">
                    <span
                        class="text-emerald-600 font-bold mr-0.5">{{ $empresa->currency_simbol }}</span>{{ number_format($propinasHoy, 2) }}
                </h3>
            </div>

            <div class="ultra-card p-6 rounded-2xl">
                <div class="flex items-center justify-between mb-5">
                    <div
                        class="icon-container w-12 h-12 bg-gradient-to-br from-sky-500 to-blue-600 rounded-xl flex items-center justify-center text-white shadow-md shadow-sky-500/20 transition-all duration-300">
                        <i class="fa-solid fa-percent text-xl"></i>
                    </div>
                    @php
                        $variacionImpuestos = $impuestosAyer > 0 ? (($impuestosHoy - $impuestosAyer) / $impuestosAyer) * 100 : 100;
                    @endphp
                    <span
                        class="text-[11px] font-black {{ $variacionImpuestos >= 0 ? 'text-emerald-700 bg-emerald-50 border-emerald-200' : 'text-rose-700 bg-rose-50 border-rose-200' }} px-2.5 py-1 rounded-lg border shadow-sm">
                        {{ $variacionImpuestos >= 0 ? '▲' : '▼' }} {{ number_format(abs($variacionImpuestos), 1) }}%
                    </span>
                </div>
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">IGV Recaudado</p>
                <h3 class="text-2xl font-black text-slate-800 tracking-tight mt-1">
                    <span class="text-sky-600 font-bold mr-0.5">{{ $empresa->currency_simbol }}</span>{{ number_format($impuestosHoy, 2) }}
                </h3>
            </div>

            <div class="ultra-card p-6 rounded-2xl">
                <div class="flex items-center justify-between mb-5">
                    <div
                        class="icon-container w-12 h-12 bg-gradient-to-br from-rose-400 to-pink-600 rounded-xl flex items-center justify-center text-white shadow-md shadow-rose-500/20 transition-all duration-300">
                        <i class="fa-solid fa-tags text-xl"></i>
                    </div>
                    @php
                        $variacionDescuentos = $descuentosAyer > 0 ? (($descuentosHoy - $descuentosAyer) / $descuentosAyer) * 100 : 100;
                    @endphp
                    <span
                        class="text-[11px] font-black {{ $variacionDescuentos <= 0 ? 'text-emerald-700 bg-emerald-50 border-emerald-200' : 'text-rose-700 bg-rose-50 border-rose-200' }} px-2.5 py-1 rounded-lg border shadow-sm">
                        {{ $variacionDescuentos <= 0 ? '▼' : '▲' }} {{ number_format(abs($variacionDescuentos), 1) }}%
                    </span>
                </div>
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">Descuentos Otorgados</p>
                <h3 class="text-2xl font-black text-slate-800 tracking-tight mt-1">
                    <span class="text-rose-600 font-bold mr-0.5">{{ $empresa->currency_simbol }}</span>{{ number_format($descuentosHoy, 2) }}
                </h3>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">

            <div class="lg:col-span-2 ultra-card card-ventas rounded-2xl p-6">
                <div class="flex items-center justify-between mb-2">
                    <div>
                        <h3 class="font-black text-slate-800 text-sm uppercase tracking-wider flex items-center gap-2">
                            <i class="fa-solid fa-chart-bar text-orange-500"></i>
                            Tendencia de Ventas
                        </h3>
                        <p class="text-[11px] text-slate-400 font-medium mt-0.5">Historial analítico de los últimos 6
                            meses</p>
                    </div>
                    <span
                        class="text-[10px] bg-orange-50 text-orange-600 font-bold px-2.5 py-1 rounded-md uppercase tracking-wide border border-orange-100">Mensual</span>
                </div>
                <div class="w-full h-72">
                    <canvas id="chartVentasMensuales"></canvas>
                </div>
            </div>

            <div class="ultra-card card-ticket rounded-2xl p-6 flex flex-col justify-between">
                <div>
                    <h3 class="font-black text-slate-800 text-sm uppercase tracking-wider flex items-center gap-2">
                        <i class="fa-solid fa-pie-chart text-indigo-500"></i>
                        Distribución de Ingresos
                    </h3>
                    <p class="text-[11px] text-slate-400 font-medium mt-0.5">Flujo por método de pago hoy</p>
                </div>
                <div class="w-full h-64 flex items-center justify-center mt-4">
                    <div class="w-full h-full relative flex items-center justify-center">
                        <canvas id="chartMetodosPago"></canvas>
                    </div>
                </div>
            </div>

        </div>

        @push('js')
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    const currencySimbol = "{{ $empresa->currency_simbol }}";
                    const ctxVentas = document.getElementById('chartVentasMensuales').getContext('2d');

                    const gradientVentas = ctxVentas.createLinearGradient(0, 0, 0, 300);
                    gradientVentas.addColorStop(0, '#f97316');
                    gradientVentas.addColorStop(1, '#ea580c');

                    const chartVentas = new Chart(ctxVentas, {
                        type: 'bar',
                        data: {
                            labels: {!! json_encode($chartVentasMesLabels) !!},
                            datasets: [{
                                label: 'Ventas Totales',
                                data: {!! json_encode($chartVentasMesData) !!},
                                backgroundColor: gradientVentas,
                                borderRadius: 6,
                                borderSkipped: false
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            return ' ' + context.dataset.label + ': ' + currencySimbol + context
                                                .raw.toLocaleString('es-PE', {
                                                    minimumFractionDigits: 2
                                                });
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    grid: {
                                        display: false
                                    },
                                    ticks: {
                                        color: '#94a3b8',
                                        font: {
                                            family: 'Quicksand, sans-serif',
                                            size: 11,
                                            weight: 600
                                        }
                                    }
                                },
                                y: {
                                    grid: {
                                        color: '#f1f5f9'
                                    },
                                    border: {
                                        dash: [4, 4]
                                    },
                                    ticks: {
                                        color: '#94a3b8',
                                        font: {
                                            family: 'Quicksand, sans-serif',
                                            size: 11,
                                            weight: 600
                                        },
                                        callback: function(value) {
                                            return currencySimbol + value;
                                        }
                                    }
                                }
                            }
                        }
                    });


                    // ====================================================
                    // 2. CONFIGURACIÓN: GRÁFICO DE DONA (MÉTODOS DE PAGO)
                    // ====================================================
                    const dataMetodos = {!! json_encode($chartMetodosData) !!};
                    const labelsMetodos = {!! json_encode($chartMetodosLabels) !!};

                    if (dataMetodos.length === 0) {
                        document.getElementById("chartMetodosPago").parentElement.innerHTML = `
                        <div class="text-center text-xs font-semibold text-slate-400 py-8">
                            No hay transacciones registradas hoy
                        </div>`;
                    } else {
                        const ctxMetodos = document.getElementById('chartMetodosPago').getContext('2d');

                        // Colores base de tus cards aplicados a la dona:
                        // card-ticket (#10b981), card-cocina (#6366f1), card-mesas (#f59e0b), card-ventas (#f97316)
                        const coloresCards = ['#10b981', '#6366f1', '#f59e0b', '#f97316'];

                        const chartMetodos = new Chart(ctxMetodos, {
                            type: 'doughnut',
                            data: {
                                labels: labelsMetodos,
                                datasets: [{
                                    data: dataMetodos,
                                    backgroundColor: coloresCards,
                                    borderWidth: 3,
                                    borderColor: '#ffffff'
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                cutout: '75%',
                                plugins: {
                                    legend: {
                                        position: 'bottom',
                                        labels: {
                                            color: '#475569',
                                            font: {
                                                family: 'Quicksand, sans-serif',
                                                size: 11,
                                                weight: 700
                                            },
                                            boxWidth: 12,
                                            padding: 15
                                        }
                                    },
                                    tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                return ' ' + context.label + ': ' + currencySimbol + context.raw
                                                    .toFixed(2);
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    }
                });
            </script>
        @endpush

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <div
                class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-xl shadow-slate-100 overflow-hidden">
                <div
                    class="px-6 py-5 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white flex justify-between items-center">
                    <h3
                        class="font-black text-slate-800 tracking-tight flex items-center gap-2.5 text-sm uppercase tracking-wider">
                        <span class="w-2 h-2 rounded-full bg-orange-500"></span>
                        Monitor de Comandas Recientes
                    </h3>
                    <span
                        class="text-[11px] bg-slate-100 text-slate-600 font-bold px-2.5 py-1 rounded-full border border-slate-200">Hoy</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr
                                class="text-slate-400 font-bold uppercase text-[10px] tracking-widest border-b border-slate-100 bg-slate-50/70">
                                <th class="px-6 py-4">Pedido</th>
                                <th class="px-6 py-4">Ubicación / Cliente</th>
                                <th class="px-6 py-4 text-center">Estado</th>
                                <th class="px-6 py-4 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($ultimasComandas as $order)
                                <tr class="group hover:bg-orange-50/30 transition-all duration-200">
                                    <td class="px-6 py-4.5 font-black text-orange-600 text-sm">#{{ $order->id }}
                                    </td>
                                    <td class="px-6 py-4.5">
                                        <div class="font-bold text-slate-800 text-sm flex items-center gap-1.5">
                                            <i class="fa-solid fa-chair text-slate-400 text-xs"></i>
                                            {{ $order->table->name ?? 'Para llevar' }}
                                        </div>
                                        <div
                                            class="text-[11px] text-slate-400 font-medium uppercase tracking-wide mt-0.5 pl-5">
                                            {{ $order->customer_name }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4.5 text-center">
                                        @php
                                            $statusClasses = match ($order->status) {
                                                'pendiente'
                                                    => 'bg-gradient-to-r from-amber-500 to-orange-500 text-white shadow-md shadow-orange-500/10 border-transparent',
                                                'pagado'
                                                    => 'bg-gradient-to-r from-emerald-500 to-teal-500 text-white shadow-md shadow-emerald-500/10 border-transparent',
                                                'cancelado'
                                                    => 'bg-gradient-to-r from-rose-500 to-red-600 text-white shadow-md shadow-red-500/10 border-transparent',
                                                default => 'bg-slate-100 text-slate-700 border-slate-200',
                                            };
                                        @endphp
                                        <span
                                            class="inline-flex items-center px-3 py-1 rounded-xl text-[10px] font-black border {{ $statusClasses }} uppercase tracking-wider">
                                            {{ $order->status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4.5 text-right font-black text-slate-900 text-sm">
                                        <span
                                            class="text-slate-400 font-bold text-xs mr-0.5">{{ $empresa->currency_simbol }}</span>{{ number_format($order->total, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4"
                                        class="px-6 py-12 text-center text-slate-400 font-medium italic bg-slate-50/50">
                                        <i class="fa-solid fa-receipt text-2xl text-slate-300 block mb-2"></i>
                                        No hay órdenes registradas hoy
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-xl shadow-slate-100 p-6">
                <h3
                    class="font-black text-slate-800 text-xs tracking-widest uppercase border-b border-slate-100 pb-4 mb-5 flex items-center justify-between">
                    <span>🔥 Top 5 Productos</span>
                    <span
                        class="text-[10px] text-orange-600 bg-orange-50 px-2 py-0.5 rounded font-bold uppercase tracking-normal">Más
                        vendidos</span>
                </h3>

                <div class="space-y-4">
                    @forelse ($rankingProductos as $item)
                        <div
                            class="flex items-center justify-between group p-2.5 rounded-xl hover:bg-slate-50 transition-all duration-200 border border-transparent hover:border-slate-100">
                            <div class="flex items-center gap-3.5">
                                <div
                                    class="w-10 h-10 rounded-xl bg-gradient-to-br from-slate-100 to-slate-200 border border-slate-300/50 flex items-center justify-center text-xs font-black text-slate-600 group-hover:from-orange-500 group-hover:to-orange-600 group-hover:text-white group-hover:shadow-md group-hover:shadow-orange-500/20 group-hover:border-transparent transition-all duration-300 uppercase tracking-tighter">
                                    {{ mb_substr($item->product->name, 0, 2) }}
                                </div>
                                <div>
                                    <p
                                        class="text-xs font-bold text-slate-800 group-hover:text-orange-600 transition-colors">
                                        {{ $item->product->name }}</p>
                                    <p class="text-[11px] font-semibold text-slate-400 mt-0.5">
                                        Contador: <span
                                            class="text-slate-700 font-bold bg-slate-100 px-1.5 py-0.5 rounded text-[10px] ml-0.5 group-hover:bg-orange-50 group-hover:text-orange-700">{{ (int) $item->total_qty }}
                                            u.</span>
                                    </p>
                                </div>
                            </div>
                            <span
                                class="text-xs font-black text-slate-800 bg-slate-100 px-2.5 py-1.5 rounded-xl border border-slate-200/60 group-hover:bg-white group-hover:border-orange-200 transition-all">
                                <span
                                    class="text-[10px] text-slate-400 font-bold mr-0.5">{{ $empresa->currency_simbol }}</span>{{ number_format($item->total_money, 2) }}
                            </span>
                        </div>
                    @empty
                        <div class="flex flex-col items-center justify-center py-12 text-center">
                            <div
                                class="w-12 h-12 rounded-full bg-slate-50 flex items-center justify-center text-slate-300 border border-slate-100 mb-3">
                                <i class="fa-solid fa-utensils text-lg"></i>
                            </div>
                            <p class="text-xs font-medium text-slate-400 italic">Sin datos de venta</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
