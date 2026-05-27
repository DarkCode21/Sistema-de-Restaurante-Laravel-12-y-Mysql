<x-admin-layout>
    <div class="p-6 space-y-6">
        <div class="flex flex-col md:flex-row justify-between items-end gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Balance General</h2>
                <p class="text-gray-500">Resumen de rendimiento por categorías.</p>
            </div>
            <div class="bg-orange-600 px-6 py-4 rounded-3xl text-white shadow-lg shadow-orange-200">
                <p class="text-[10px] uppercase font-bold opacity-80">Balance Neto del Periodo</p>
                <p class="text-2xl font-bold">{{ $empresa->currency_simbol }}{{ number_format($balanceNeto, 2) }}</p>
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

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white rounded-3xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="bg-emerald-50 px-6 py-4 border-b border-emerald-100">
                    <h3 class="text-emerald-700 font-bold flex items-center">
                        <i class="fa-solid fa-circle-arrow-up mr-2"></i> Detalle de Ingresos
                    </h3>
                </div>
                <table class="w-full text-sm text-left">
                    <tbody class="divide-y divide-gray-50">
                        @foreach($ingresosPorCategoria as $item)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-gray-600">{{ $item->category->name }}</td>
                            <td class="px-6 py-4 text-right font-bold text-emerald-600">{{ $empresa->currency_simbol }}{{ number_format($item->total, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-emerald-50/30">
                            <td class="px-6 py-4 font-bold text-emerald-800">TOTAL INGRESOS</td>
                            <td class="px-6 py-4 text-right font-black text-emerald-800">{{ $empresa->currency_simbol }}{{ number_format($totalIngresos, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="bg-white rounded-3xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="bg-red-50 px-6 py-4 border-b border-red-100">
                    <h3 class="text-red-700 font-bold flex items-center">
                        <i class="fa-solid fa-circle-arrow-down mr-2"></i> Detalle de Egresos
                    </h3>
                </div>
                <table class="w-full text-sm text-left">
                    <tbody class="divide-y divide-gray-50">
                        @foreach($egresosPorCategoria as $item)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-gray-600">{{ $item->category->name }}</td>
                            <td class="px-6 py-4 text-right font-bold text-red-600">{{ $empresa->currency_simbol }}{{ number_format($item->total, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-red-50/30">
                            <td class="px-6 py-4 font-bold text-red-800">TOTAL EGRESOS</td>
                            <td class="px-6 py-4 text-right font-black text-red-800">{{ $empresa->currency_simbol }}{{ number_format($totalEgresos, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</x-admin-layout>