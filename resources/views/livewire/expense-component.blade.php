<div class="min-h-screen antialiased">
    <div class="mx-auto">

        {{-- HEADER --}}
        <div class="flex flex-col lg:flex-row gap-4 items-start lg:items-center justify-between mb-4">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Gastos</h1>
                <p class="text-slate-500 text-xs font-medium">Gestiona y controla las salidas de dinero de las cajas</p>
            </div>

            {{-- FILTROS Y BOTONES ACCIÓN --}}
            <div class="flex flex-col sm:flex-row flex-wrap lg:flex-nowrap w-full lg:w-auto items-center gap-3">

                {{-- Input Buscar --}}
                <div class="relative w-full sm:w-60 group">
                    <i
                        class="fa-solid fa-magnifying-glass absolute left-4 top-3 text-slate-400 group-focus-within:text-orange-500 transition-colors"></i>
                    <input wire:model.live="search" type="text" placeholder="Buscar gasto..."
                        class="w-full bg-white border border-slate-200 rounded-2xl pl-11 pr-4 py-2.5 text-sm shadow-sm focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 outline-none transition-all">
                </div>

                {{-- Filtro Rango de Fechas --}}
                <div
                    class="flex items-center w-full sm:w-auto bg-white border border-slate-200 rounded-2xl px-3 py-1 shadow-sm gap-2">
                    <div class="flex items-center gap-1">
                        <span class="text-[10px] font-bold uppercase text-slate-400">Desde:</span>
                        <input wire:model.live="start_date" type="date"
                            class="border-0 p-1 text-xs font-semibold text-slate-700 outline-none focus:ring-0">
                    </div>
                    <div class="h-4 w-px bg-slate-200"></div>
                    <div class="flex items-center gap-1">
                        <span class="text-[10px] font-bold uppercase text-slate-400">Hasta:</span>
                        <input wire:model.live="end_date" type="date"
                            class="border-0 p-1 text-xs font-semibold text-slate-700 outline-none focus:ring-0">
                    </div>
                </div>





            </div>
        </div>

        {{-- TABLE CARD --}}
        <div class="bg-white border border-slate-200 overflow-hidden shadow-sm">

            <div class="px-8 py-6 border-b border-slate-100 flex items-center justify-between bg-white">

                @can('gastos.reportes')
                    <div
                        class="inline-flex rounded-2xl shadow-sm bg-white border border-slate-200 p-1 w-full sm:w-auto justify-center">
                        <a href="{{ $pdfUrl }}" target="_blank"
                            class="px-4 py-1.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-rose-50 hover:text-rose-600 transition-all flex items-center gap-1.5">
                            <i class="fa-solid fa-file-pdf text-rose-500"></i> PDF
                        </a>
                        <div class="w-px bg-slate-200 my-1 mx-1"></div>
                        <a href="{{ $excelUrl }}" target="_blank"
                            class="px-4 py-1.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-emerald-50 hover:text-emerald-600 transition-all flex items-center gap-1.5">
                            <i class="fa-solid fa-file-excel text-emerald-500"></i> Excel
                        </a>
                    </div>
                @endcan
                @can('gastos.crear')
                    <button wire:click="create"
                        class="bg-orange-600 hover:bg-orange-700 text-white px-5 py-2.5 rounded-2xl text-xs font-bold transition-all shadow-md shadow-orange-100 flex items-center gap-2 active:scale-95 w-full sm:w-auto justify-center whitespace-nowrap">
                        <i class="fa-solid fa-plus"></i> Nuevo Gasto
                    </button>
                @endcan
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-slate-400 text-[10px] uppercase tracking-[0.15em] bg-slate-50/50">
                            <th class="px-8 py-5 font-bold">Concepto</th>
                            <th class="px-8 py-5 font-bold">Caja / Método</th> {{-- Combinamos las cabeceras --}}
                            <th class="px-8 py-5 font-bold">Monto</th>
                            <th class="px-8 py-5 font-bold">Fecha / Usuario</th>
                            <th class="px-8 py-5 font-bold text-right">Acciones</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @forelse($expenses as $expense)
                            <tr class="hover:bg-slate-50/80 transition-all duration-200 group">

                                {{-- CONCEPTO Y DESCRIPCIÓN --}}
                                <td class="px-8 py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center border border-rose-100/80 shadow-sm group-hover:bg-rose-600 group-hover:text-white group-hover:border-rose-600 transition-all duration-200">
                                            <i class="fa-solid fa-money-bill-transfer text-xs"></i>
                                        </div>
                                        <div class="flex flex-col">
                                            <p
                                                class="text-sm font-bold text-slate-700 group-hover:text-rose-600 transition-colors duration-150 transform group-hover:translate-x-0.5 ease-out">
                                                {{ $expense->concept }}
                                            </p>
                                            @if ($expense->description)
                                                <p class="text-slate-400 text-[11px] font-medium truncate max-w-[220px] mt-0.5"
                                                    title="{{ $expense->description }}">
                                                    {{ $expense->description }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                {{-- CAJA Y MÉTODO DE PAGO (UNO ABAJO DEL OTRO) --}}
                                <td class="px-8 py-4">
                                    <div class="flex flex-col items-start gap-1.5">
                                        {{-- Badge de la Caja --}}
                                        <span
                                            class="inline-flex items-center gap-1 text-xs font-bold text-slate-600 bg-slate-100/80 border border-slate-200/60 px-2.5 py-1 rounded-xl shadow-sm">
                                            <i class="fa-solid fa-cash-register text-[10px] text-slate-400"></i>
                                            {{ $expense->cashRegister->name }}
                                        </span>

                                        {{-- Badge del Método de Pago justo debajo --}}
                                        @if ($expense->paymentMethod->is_efectivo)
                                            <span
                                                class="inline-flex items-center gap-1 text-[11px] font-bold text-amber-700 bg-amber-50 border border-amber-200/60 px-2.5 py-1 rounded-xl shadow-sm shadow-amber-50/50">
                                                <span
                                                    class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                                {{ $expense->paymentMethod->name }}
                                            </span>
                                        @else
                                            <span
                                                class="inline-flex items-center gap-1 text-[11px] font-bold text-blue-700 bg-blue-50 border border-blue-200/60 px-2.5 py-1 rounded-xl shadow-sm shadow-blue-50/50">
                                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                                {{ $expense->paymentMethod->name }}
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                {{-- MONTO DEL GASTO (CORREGIDO) --}}
                                <td class="px-8 py-4">
                                    <span
                                        class="inline-flex items-center whitespace-nowrap text-sm font-black text-rose-600 bg-rose-50/50 border border-rose-100/50 px-2.5 py-1 rounded-xl">
                                        -{{ $empresa->currency_simbol ?? 'S/' }}{{ number_format($expense->amount, 2) }}
                                    </span>
                                </td>

                                {{-- FECHA Y USUARIO --}}
                                <td class="px-8 py-4">
                                    <div class="flex flex-col">
                                        <p class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                                            <i class="fa-regular fa-calendar text-slate-400 text-[11px]"></i>
                                            {{ $expense->expense_date->format('d/m/Y h:i A') }}
                                        </p>
                                        <p
                                            class="text-[10px] font-semibold text-slate-400 flex items-center gap-1 mt-1 bg-slate-50 border border-slate-100 w-fit px-1.5 py-0.5 rounded-lg">
                                            <i class="fa-solid fa-user-shield text-[9px] text-slate-400"></i>
                                            {{ $expense->user->name }}
                                        </p>
                                    </div>
                                </td>

                                {{-- ACCIONES --}}
                                <td class="px-8 py-4">
                                    <div
                                        class="flex justify-end gap-2 opacity-80 group-hover:opacity-100 transition-opacity">
                                        @can('gastos.editar')
                                            <button wire:click="edit({{ $expense->id }})"
                                                class="w-9 h-9 flex items-center justify-center rounded-xl bg-white text-slate-400 hover:text-amber-600 hover:border-amber-300 hover:bg-amber-50/30 transition-all border border-slate-200 shadow-sm active:scale-90">
                                                <i class="fa-solid fa-pen text-[10px]"></i>
                                            </button>
                                        @endcan
                                        @can('gastos.eliminar')
                                            <button wire:click="deleteConfirm({{ $expense->id }})"
                                                class="w-9 h-9 flex items-center justify-center rounded-xl bg-white text-slate-400 hover:text-rose-600 hover:border-rose-300 hover:bg-rose-50/30 transition-all border border-slate-200 shadow-sm active:scale-90">
                                                <i class="fa-solid fa-trash-can text-[10px]"></i>
                                            </button>
                                        @endcan
                                    </div>
                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-8 py-24 text-center bg-slate-50/20"> {{-- Cambiado a colspan="5" --}}
                                    <div
                                        class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-slate-100 text-slate-300 mb-4 border border-slate-200/60 shadow-inner">
                                        <i class="fa-solid fa-receipt text-2xl"></i>
                                    </div>
                                    <p class="text-slate-500 text-sm font-bold tracking-tight">No se encontraron egresos
                                    </p>
                                    <p class="text-slate-400 text-xs font-medium mt-1">Prueba cambiando los filtros de
                                        fechas o el término de búsqueda.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-8 py-5 border-t border-slate-100 bg-slate-50/30">
                {{ $expenses->links() }}
            </div>
        </div>
    </div>

    {{-- MODAL --}}
    @if ($isOpen)
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center z-[100] p-4">
            <div class="bg-white rounded-[2rem] w-full max-w-lg shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">

                {{-- HEADER --}}
                <div class="px-8 py-6 bg-slate-50 border-b flex-shrink-0">
                    <h3 class="text-lg font-black text-slate-800">
                        {{ $expense_id ? 'Editar Gasto' : 'Nuevo Gasto' }}
                    </h3>
                    <p class="text-xs text-slate-500 font-medium">Complete los detalles de la salida de dinero</p>
                </div>

                {{-- FORMULARIO --}}
                <form wire:submit.prevent="store" class="flex flex-col overflow-hidden">

                    <div class="px-8 py-8 grid grid-cols-1 md:grid-cols-2 gap-6 overflow-y-auto custom-scrollbar">

                        <div class="md:col-span-2">
                            <label class="text-[10px] font-bold uppercase text-slate-400 tracking-wider">Concepto del
                                Gasto</label>
                            <input wire:model="concept" type="text"
                                placeholder="Ej: Pago de verduras, Recibo de luz..."
                                class="w-full border-slate-200 rounded-xl px-4 py-3 text-sm focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 outline-none transition-all mt-1">
                            @error('concept')
                                <span class="text-red-500 text-[10px] font-bold mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label class="text-[10px] font-bold uppercase text-slate-400 tracking-wider">Caja
                                Afectada</label>
                            <select wire:model="cash_register_id"
                                class="w-full border-slate-200 rounded-xl px-4 py-3 text-sm focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 outline-none transition-all mt-1">
                                <option value="">Seleccionar caja...</option>
                                @foreach ($cashRegisters as $register)
                                    <option value="{{ $register->id }}">{{ $register->name }}
                                        ({{ $empresa->currency_simbol ?? 'S/' }}{{ number_format($register->current_amount, 2) }})
                                    </option>
                                @endforeach
                            </select>
                            @error('cash_register_id')
                                <span class="text-red-500 text-[10px] font-bold mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label class="text-[10px] font-bold uppercase text-slate-400 tracking-wider">Método de
                                Pago</label>
                            <select wire:model="payment_method_id"
                                class="w-full border-slate-200 rounded-xl px-4 py-3 text-sm focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 outline-none transition-all mt-1">
                                <option value="">Seleccionar método...</option>
                                @foreach ($paymentMethods as $method)
                                    <option value="{{ $method->id }}">{{ $method->name }}</option>
                                @endforeach
                            </select>
                            @error('payment_method_id')
                                <span class="text-red-500 text-[10px] font-bold mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label class="text-[10px] font-bold uppercase text-slate-400 tracking-wider">Monto del
                                Gasto</label>
                            <div class="relative">
                                <span
                                    class="absolute left-4 top-[17px] text-slate-400 text-sm font-bold">{{ $empresa->currency_simbol ?? 'S/' }}</span>
                                <input wire:model="amount" type="number" step="0.01" placeholder="0.00"
                                    class="w-full border-slate-200 rounded-xl pl-10 pr-4 py-3 text-sm focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 outline-none transition-all mt-1">
                            </div>
                            @error('amount')
                                <span class="text-red-500 text-[10px] font-bold mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label class="text-[10px] font-bold uppercase text-slate-400 tracking-wider">Fecha del
                                Gasto</label>
                            <input wire:model="expense_date" type="datetime-local"
                                class="w-full border-slate-200 rounded-xl px-4 py-3 text-sm focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 outline-none transition-all mt-1">
                            @error('expense_date')
                                <span class="text-red-500 text-[10px] font-bold mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="text-[10px] font-bold uppercase text-slate-400 tracking-wider">Descripción /
                                Notas adicionales</label>
                            <textarea wire:model="description" rows="3" placeholder="Detalles u observaciones del gasto..."
                                class="w-full border-slate-200 rounded-xl px-4 py-3 text-sm focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 outline-none transition-all mt-1 resize-none"></textarea>
                            @error('description')
                                <span class="text-red-500 text-[10px] font-bold mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>

                    </div>

                    {{-- ACCIONES --}}
                    <div class="px-8 py-6 bg-slate-50 flex justify-end gap-3 border-t flex-shrink-0">
                        <button type="button" wire:click="closeModal"
                            class="px-5 py-2.5 text-xs font-bold text-slate-500 hover:bg-slate-200 rounded-xl transition-colors">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="px-6 py-2.5 bg-orange-600 hover:bg-orange-700 text-white text-xs font-bold rounded-xl shadow-lg shadow-orange-500/30 transition-all active:scale-95">
                            <i class="fa-solid fa-floppy-disk mr-2"></i> Registrar Gasto
                        </button>
                    </div>

                </form>
            </div>
        </div>
    @endif
</div>
