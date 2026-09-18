<x-admin-layout>
    <main class="mx-auto max-w-7xl space-y-6 p-4 sm:p-6">
        <section class="border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-5 sm:px-7">
                <p class="text-[10px] font-black uppercase tracking-[0.22em] text-orange-600">Control de rentabilidad</p>
                <div class="mt-2 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <h1 class="text-2xl font-black tracking-tight text-slate-900">Utilidad operativa</h1>
                        <p class="mt-1 text-sm text-slate-500">Resultado de ventas, costo histórico y gastos del periodo.</p>
                    </div>
                    <a href="{{ route('reports.profit', ['start_date' => $start_date, 'end_date' => $end_date, 'category_id' => $categoryId, 'product' => $productSearch, 'print' => 1]) }}" target="_blank"
                        class="inline-flex items-center justify-center gap-2 border border-slate-300 px-4 py-2 text-xs font-bold text-slate-600 transition-colors hover:border-slate-900 hover:text-slate-900">
                        <i class="fa-solid fa-print"></i> Imprimir reporte
                    </a>
                </div>
            </div>

            <form action="{{ route('reports.profit') }}" class="grid gap-3 bg-slate-50 px-5 py-4 sm:grid-cols-2 lg:grid-cols-5 sm:px-7">
                <label class="block text-[10px] font-black uppercase tracking-wider text-slate-500">Desde
                    <input type="date" name="start_date" value="{{ $start_date }}" class="mt-1.5 w-full border-slate-300 bg-white text-sm focus:border-orange-500 focus:ring-orange-500">
                </label>
                <label class="block text-[10px] font-black uppercase tracking-wider text-slate-500">Hasta
                    <input type="date" name="end_date" value="{{ $end_date }}" class="mt-1.5 w-full border-slate-300 bg-white text-sm focus:border-orange-500 focus:ring-orange-500">
                </label>
                <label class="block text-[10px] font-black uppercase tracking-wider text-slate-500">Categoría
                    <select name="category_id" class="mt-1.5 w-full border-slate-300 bg-white text-sm focus:border-orange-500 focus:ring-orange-500">
                        <option value="">Todas las categorías</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((int) $categoryId === $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block text-[10px] font-black uppercase tracking-wider text-slate-500">Producto
                    <input type="search" name="product" value="{{ $productSearch }}" placeholder="Nombre del producto" class="mt-1.5 w-full border-slate-300 bg-white text-sm focus:border-orange-500 focus:ring-orange-500">
                </label>
                <div class="flex items-end gap-2">
                    <button class="flex-1 bg-slate-900 px-4 py-2.5 text-xs font-black uppercase tracking-wide text-white transition-colors hover:bg-orange-600">Aplicar</button>
                    <a href="{{ route('reports.profit') }}" class="border border-slate-300 px-3 py-2.5 text-xs font-bold text-slate-500 hover:text-slate-900">Limpiar</a>
                </div>
            </form>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:px-7">
            <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div><p class="text-sm font-black text-slate-900">Lectura del periodo</p><p class="text-xs text-slate-500">{{ \Carbon\Carbon::parse($start_date)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($end_date)->format('d/m/Y') }}@if ($categoryId) · {{ $categories->firstWhere('id', $categoryId)?->name }}@endif@if ($productSearch) · {{ $productSearch }}@endif</p></div>
                <p class="text-xs font-bold text-slate-500">{{ $totals['costed_lines'] }} líneas con costo histórico</p>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                <section class="relative overflow-hidden rounded-2xl border border-white/20 bg-gradient-to-br from-cyan-500 to-blue-600 p-5 text-white shadow-[0_8px_30px_rgb(6,182,212,0.25)]"><i class="fa-solid fa-cash-register absolute -right-3 -top-4 text-8xl text-white/10"></i><p class="relative text-[10px] font-black uppercase tracking-widest text-cyan-100">Ventas netas</p><p class="relative mt-2 text-3xl font-black tracking-tight">{{ $empresa->currency_simbol }}{{ number_format($totals['sales'], 2) }}</p></section>
                <section class="relative overflow-hidden rounded-2xl border border-white/20 bg-gradient-to-br from-rose-500 to-red-700 p-5 text-white shadow-[0_8px_30px_rgb(244,63,94,0.25)]"><i class="fa-solid fa-receipt absolute -right-3 -top-4 text-8xl text-white/10"></i><p class="relative text-[10px] font-black uppercase tracking-widest text-rose-100">Costo vendido</p><p class="relative mt-2 text-3xl font-black tracking-tight">-{{ $empresa->currency_simbol }}{{ number_format($totals['cost'], 2) }}</p></section>
                <section class="relative overflow-hidden rounded-2xl border border-white/20 bg-gradient-to-br from-emerald-500 to-teal-600 p-5 text-white shadow-[0_8px_30px_rgb(16,185,129,0.25)]"><i class="fa-solid fa-chart-line absolute -right-3 -top-4 text-8xl text-white/10"></i><p class="relative text-[10px] font-black uppercase tracking-widest text-emerald-100">Utilidad bruta</p><p class="relative mt-2 text-3xl font-black tracking-tight">{{ $empresa->currency_simbol }}{{ number_format($totals['gross_profit'], 2) }}</p></section>
                <section class="relative overflow-hidden rounded-2xl border border-white/20 bg-gradient-to-br from-orange-500 to-amber-600 p-5 text-white shadow-[0_8px_30px_rgb(249,115,22,0.25)]"><i class="fa-solid fa-file-invoice-dollar absolute -right-3 -top-4 text-8xl text-white/10"></i><p class="relative text-[10px] font-black uppercase tracking-widest text-orange-100">Gastos</p><p class="relative mt-2 text-3xl font-black tracking-tight">-{{ $empresa->currency_simbol }}{{ number_format($totals['expenses'], 2) }}</p></section>
                <section class="relative overflow-hidden rounded-2xl border border-white/20 {{ $totals['net_profit'] >= 0 ? 'bg-gradient-to-br from-violet-500 to-purple-700 shadow-[0_8px_30px_rgb(139,92,246,0.25)]' : 'bg-gradient-to-br from-slate-700 to-slate-900 shadow-[0_8px_30px_rgb(15,23,42,0.2)]' }} p-5 text-white"><i class="fa-solid fa-scale-balanced absolute -right-3 -top-4 text-8xl text-white/10"></i><p class="relative text-[10px] font-black uppercase tracking-widest text-white/70">Resultado operativo</p><p class="relative mt-2 text-3xl font-black tracking-tight">{{ $empresa->currency_simbol }}{{ number_format($totals['net_profit'], 2) }}</p></section>
            </div>
        </section>

        @if ($totals['missing_cost_lines'])
            <div class="border-l-4 border-amber-500 bg-amber-50 px-5 py-4 text-sm text-amber-900">
                <i class="fa-solid fa-triangle-exclamation mr-2"></i>{{ $totals['missing_cost_lines'] }} líneas del periodo no tienen costo histórico; no intervienen en la utilidad bruta.
            </div>
        @endif

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_19rem]">
            <section class="overflow-hidden border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 sm:px-6">
                    <div><h2 class="font-black text-slate-900">Rentabilidad por producto</h2><p class="mt-0.5 text-xs text-slate-500">Ordenado por utilidad bruta.</p></div>
                    <span class="text-xs font-bold text-slate-500">{{ $products->total() }} productos</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[640px] text-left text-sm">
                        <thead class="border-b border-slate-200 bg-slate-50 text-[10px] font-black uppercase tracking-wider text-slate-500">
                            <tr><th class="px-5 py-3">Producto</th><th class="px-5 py-3 text-right">Unidades</th><th class="px-5 py-3 text-right">Costo</th><th class="px-5 py-3 text-right">Utilidad</th><th class="px-5 py-3 text-right">Margen</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($products as $product)
                                @php
                                    $revenue = (float) $product->cost + (float) $product->gross_profit;
                                    $margin = $revenue == 0.0 ? 0 : ((float) $product->gross_profit / $revenue) * 100;
                                @endphp
                                <tr class="hover:bg-slate-50">
                                    <td class="px-5 py-4 font-bold text-slate-800">{{ $product->product_name ?: 'Producto histórico' }}</td>
                                    <td class="px-5 py-4 text-right text-slate-600">{{ rtrim(rtrim(number_format($product->quantity, 3, '.', ''), '0'), '.') }}</td>
                                    <td class="px-5 py-4 text-right text-rose-700">{{ $empresa->currency_simbol }}{{ number_format($product->cost, 2) }}</td>
                                    <td class="px-5 py-4 text-right font-black {{ $product->gross_profit >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">{{ $empresa->currency_simbol }}{{ number_format($product->gross_profit, 2) }}</td>
                                    <td class="px-5 py-4 text-right font-bold text-slate-600">{{ number_format($margin, 1) }}%</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-5 py-14 text-center text-sm text-slate-400">No hay ventas con costo histórico para estos filtros.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-200 bg-slate-50/60 px-5 py-4 sm:px-6">{{ $products->links() }}</div>
            </section>

            <aside class="border border-amber-200 bg-amber-50/70 p-5 shadow-sm">
                <div class="flex items-start gap-3"><span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700"><i class="fa-solid fa-box-open"></i></span><div><h2 class="font-black text-amber-950">Reposición pendiente</h2><p class="mt-0.5 text-xs text-amber-800">Insumos al mínimo o por debajo.</p></div></div>
                <div class="mt-5 space-y-3">
                    @forelse ($lowStockIngredients as $ingredient)
                        <div class="border-b border-amber-200 pb-3 last:border-0 last:pb-0"><p class="text-sm font-bold text-amber-950">{{ $ingredient->name }}</p><p class="mt-1 text-xs text-amber-800">Disponible: {{ rtrim(rtrim(number_format($ingredient->stock, 3, '.', ''), '0'), '.') }} {{ $ingredient->unit }} · Mínimo: {{ rtrim(rtrim(number_format($ingredient->minimum_stock, 3, '.', ''), '0'), '.') }}</p></div>
                    @empty
                        <p class="py-4 text-sm text-amber-800">No hay alertas de reposición.</p>
                    @endforelse
                </div>
            </aside>
        </div>
    </main>
</x-admin-layout>
