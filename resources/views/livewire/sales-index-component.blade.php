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
                        <label class="text-[10px] font-black text-slate-400 uppercase ml-1 mb-1 block">Mesa</label>
                        <div class="relative">
                            <i
                                class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            <input wire:model.live="search" type="text" placeholder="Buscar..."
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

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">

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
        <div class="bg-white border border-slate-200 rounded-3xl shadow-sm overflow-hidden">

            {{-- DESKTOP --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/50 border-b border-slate-100">
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase">Venta / Fecha</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase">Mesa</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase">Mesero</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase text-right">Total</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase text-center">Ticket
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
                                            {{ $sale->created_at->format('d/m/Y H:i') }}
                                        </span>
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <span
                                        class="bg-slate-100 text-slate-700 px-3 py-1 rounded-lg text-[10px] font-black uppercase">
                                        {{ $sale->order->table->name ?? 'N/A' }}
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    <span class="text-xs font-bold text-slate-600 uppercase">
                                        {{ $sale->order->user->name ?? 'Sistema' }}
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
                                    <a href="{{ route('sales.receipt', $sale->id) }}" target="_blank"
                                        class="inline-flex items-center justify-center h-9 w-9 rounded-xl bg-slate-100 text-slate-600 hover:bg-slate-800 hover:text-white transition-all shadow-sm">
                                        <i class="fas fa-print"></i>
                                    </a>
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
                                    {{ $sale->created_at->format('d M, Y h:i A') }}
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
                            <span>Mesa: {{ $sale->order->table->name ?? 'N/A' }}</span>
                            <span>{{ $sale->order->user->name ?? 'Sistema' }}</span>
                        </div>

                        <button wire:click="printTicket({{ $sale->id }})"
                            class="bg-slate-800 text-white px-4 py-1.5 rounded-lg text-[10px] font-black uppercase flex items-center gap-2">
                            <i class="fas fa-print"></i> Ticket
                        </button>

                    </div>
                @endforeach
            </div>

            <div class="p-4 md:p-6 border-t border-slate-50 bg-slate-50/30">
                {{ $sales->links() }}
            </div>

        </div>
    </div>
</div>
