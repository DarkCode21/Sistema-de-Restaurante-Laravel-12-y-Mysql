<x-admin-layout>
    <div class="space-y-6 p-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div><h1 class="text-2xl font-black tracking-tight text-slate-800">Utilidad operativa</h1><p class="text-sm text-slate-500">Ventas sin IGV menos costo vendido y gastos operativos.</p></div>
            <form class="flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm"><div><label class="block text-[10px] font-black uppercase tracking-wider text-slate-400">Desde</label><input type="date" name="start_date" value="{{ $start_date }}" class="mt-1 rounded-xl border-slate-200 text-sm"></div><div><label class="block text-[10px] font-black uppercase tracking-wider text-slate-400">Hasta</label><input type="date" name="end_date" value="{{ $end_date }}" class="mt-1 rounded-xl border-slate-200 text-sm"></div><button class="rounded-xl bg-slate-800 px-4 py-2 text-xs font-black uppercase text-white hover:bg-slate-950">Actualizar</button></form>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            @foreach ([['Ventas netas', $totals['sales'], 'text-slate-800'], ['Costo vendido', $totals['cost'], 'text-rose-600'], ['Utilidad bruta', $totals['gross_profit'], 'text-emerald-600'], ['Gastos operativos', $totals['expenses'], 'text-rose-600'], ['Utilidad neta', $totals['net_profit'], $totals['net_profit'] >= 0 ? 'text-emerald-600' : 'text-rose-600']] as [$label, $value, $color])
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-[10px] font-black uppercase tracking-wider text-slate-400">{{ $label }}</p><p class="mt-2 text-2xl font-black {{ $color }}">{{ $empresa->currency_simbol }}{{ number_format($value, 2) }}</p></div>
            @endforeach
        </div>

        @if ($totals['missing_cost_lines'])
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800"><i class="fa-solid fa-triangle-exclamation mr-2"></i>{{ $totals['missing_cost_lines'] }} línea(s) vendida(s) no tienen costo. La utilidad mostrada cubre {{ $totals['costed_lines'] }} línea(s) con costo histórico.</div>
        @endif

        <div class="grid gap-6 lg:grid-cols-[1fr_20rem]">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"><div class="border-b border-slate-100 px-5 py-4"><h2 class="font-black text-slate-800">Rentabilidad por producto</h2></div><div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="bg-slate-50 text-[10px] font-black uppercase tracking-wider text-slate-400"><tr><th class="px-5 py-3">Producto</th><th class="px-5 py-3 text-right">Unidades</th><th class="px-5 py-3 text-right">Costo</th><th class="px-5 py-3 text-right">Utilidad</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse ($products as $product)<tr><td class="px-5 py-4 font-bold text-slate-700">{{ $product->product_name }}</td><td class="px-5 py-4 text-right text-slate-600">{{ rtrim(rtrim(number_format($product->quantity, 3, '.', ''), '0'), '.') }}</td><td class="px-5 py-4 text-right text-rose-600">{{ $empresa->currency_simbol }}{{ number_format($product->cost, 2) }}</td><td class="px-5 py-4 text-right font-black {{ $product->gross_profit >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">{{ $empresa->currency_simbol }}{{ number_format($product->gross_profit, 2) }}</td></tr>@empty<tr><td colspan="4" class="px-5 py-12 text-center text-sm text-slate-400">Aún no hay ventas con costo histórico en este periodo.</td></tr>@endforelse</tbody></table></div></div>
            <aside class="rounded-2xl border border-amber-200 bg-amber-50 p-5"><h2 class="font-black text-amber-900"><i class="fa-solid fa-box-open mr-2"></i>Stock mínimo</h2><p class="mt-1 text-xs text-amber-700">Insumos que requieren reposición.</p><div class="mt-4 space-y-3">@forelse ($lowStockIngredients as $ingredient)<div class="border-b border-amber-200 pb-3 last:border-0"><p class="text-sm font-bold text-amber-950">{{ $ingredient->name }}</p><p class="text-xs text-amber-700">{{ rtrim(rtrim(number_format($ingredient->stock, 3, '.', ''), '0'), '.') }} {{ $ingredient->unit }} de mínimo {{ rtrim(rtrim(number_format($ingredient->minimum_stock, 3, '.', ''), '0'), '.') }}</p></div>@empty<p class="text-sm text-amber-700">No hay alertas de stock.</p>@endforelse</div></aside>
        </div>
    </div>
</x-admin-layout>
