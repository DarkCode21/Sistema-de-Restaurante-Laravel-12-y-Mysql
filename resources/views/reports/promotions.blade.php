<x-admin-layout>
    <div class="p-6 space-y-6">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Impacto de Promociones</h2>
                <p class="text-gray-500">Cuánto vendió cada promo y cuánto descuento generó.</p>
            </div>
            <div class="flex gap-3">
                <div class="bg-emerald-600 px-5 py-3 rounded-2xl text-white shadow-md">
                    <p class="text-[10px] uppercase font-bold opacity-80">Ingresos netos</p>
                    <p class="text-lg font-bold">{{ $empresa->currency_simbol }}{{ number_format($totals['net_revenue'], 2) }}</p>
                </div>
                <div class="bg-rose-600 px-5 py-3 rounded-2xl text-white shadow-md">
                    <p class="text-[10px] uppercase font-bold opacity-80">Descuento total</p>
                    <p class="text-lg font-bold">{{ $empresa->currency_simbol }}{{ number_format($totals['total_discount'], 2) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm">
            <form class="flex flex-wrap items-end gap-4">
                <div class="w-40">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Desde</label>
                    <input type="date" name="start_date" value="{{ $start_date }}" class="w-full border-gray-200 rounded-xl text-sm">
                </div>
                <div class="w-40">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Hasta</label>
                    <input type="date" name="end_date" value="{{ $end_date }}" class="w-full border-gray-200 rounded-xl text-sm">
                </div>
                <button type="submit" class="bg-gray-800 text-white px-6 py-2 rounded-xl text-sm font-bold hover:bg-black transition-all">
                    Actualizar
                </button>
                <button type="submit" name="action" value="pdf" class="border border-red-200 text-red-600 px-4 py-2 rounded-xl text-sm font-bold hover:bg-red-50">
                    <i class="fa-solid fa-file-pdf mr-2"></i> PDF
                </button>
            </form>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 text-[10px] font-black uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-4">Promoción</th>
                        <th class="px-5 py-4">Producto</th>
                        <th class="px-5 py-4 text-right">Aplicaciones</th>
                        <th class="px-5 py-4 text-right">Unidades</th>
                        <th class="px-5 py-4 text-right">Descuento</th>
                        <th class="px-5 py-4 text-right">Ingreso neto</th>
                        <th class="px-5 py-4 text-right">Ingreso bruto</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($promotions as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-4">
                                <div class="font-bold text-slate-700">{{ $row['promotion']->name }}</div>
                                <div class="text-[10px] uppercase text-emerald-600 font-bold">
                                    {{ $row['promotion']->discount_type === 'percent' ? $row['promotion']->value . '%' : $empresa->currency_simbol . number_format($row['promotion']->value, 2) }}
                                </div>
                            </td>
                            <td class="px-5 py-4 text-slate-600">{{ $row['promotion']->product->name ?? '—' }}</td>
                            <td class="px-5 py-4 text-right font-bold text-slate-700">{{ $row['times_applied'] }}</td>
                            <td class="px-5 py-4 text-right font-bold text-slate-700">{{ $row['qty'] }}</td>
                            <td class="px-5 py-4 text-right font-bold text-rose-600">
                                -{{ $empresa->currency_simbol }}{{ number_format($row['total_discount'], 2) }}
                            </td>
                            <td class="px-5 py-4 text-right font-bold text-emerald-600">
                                {{ $empresa->currency_simbol }}{{ number_format($row['net_revenue'], 2) }}
                            </td>
                            <td class="px-5 py-4 text-right font-bold text-slate-700">
                                {{ $empresa->currency_simbol }}{{ number_format($row['gross_revenue'], 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-sm text-slate-400">No hay promociones aplicadas en este periodo.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-slate-50 border-t border-slate-200">
                    <tr class="font-black text-slate-700">
                        <td class="px-5 py-4 uppercase text-[10px] tracking-wider" colspan="2">TOTAL</td>
                        <td class="px-5 py-4 text-right">{{ $totals['times_applied'] }}</td>
                        <td class="px-5 py-4 text-right">{{ $totals['qty'] }}</td>
                        <td class="px-5 py-4 text-right text-rose-600">-{{ $empresa->currency_simbol }}{{ number_format($totals['total_discount'], 2) }}</td>
                        <td class="px-5 py-4 text-right text-emerald-600">{{ $empresa->currency_simbol }}{{ number_format($totals['net_revenue'], 2) }}</td>
                        <td class="px-5 py-4 text-right">{{ $empresa->currency_simbol }}{{ number_format($totals['gross_revenue'], 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</x-admin-layout>
