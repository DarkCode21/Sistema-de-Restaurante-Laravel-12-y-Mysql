<div class="min-h-screen antialiased">
    <div class="max-w-7xl mx-auto space-y-6">

        {{-- HEADER --}}
        <div class="flex flex-col md:flex-row gap-4 items-center justify-between mb-2">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Métodos de Pago</h1>
                <p class="text-slate-500 text-xs font-medium">Gestiona las formas de pago aceptadas en el sistema</p>
            </div>

            <div class="flex w-full md:w-auto gap-3">
                <div class="relative flex-grow md:w-72 group">
                    <i
                        class="fa-solid fa-magnifying-glass absolute left-4 top-3 text-slate-400 group-focus-within:text-orange-500 transition-colors"></i>
                    <input wire:model.live="search" type="text" placeholder="Buscar método..."
                        class="w-full bg-white border border-slate-200 rounded-2xl pl-11 pr-4 py-2.5 text-sm shadow-sm focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 outline-none transition-all">
                </div>
                @can('payment_methods.crear')
                    <button wire:click="create"
                        class="bg-orange-600 hover:bg-orange-700 text-white px-6 py-2.5 rounded-2xl text-xs font-bold transition-all shadow-md shadow-orange-100 flex items-center gap-2 active:scale-95">
                        <i class="fa-solid fa-plus"></i> Nuevo Método
                    </button>
                @endcan
            </div>
        </div>

        {{-- TABLE CARD (ESTILO BLANCO) --}}
        <div class="bg-white border border-slate-200 rounded-[2rem] overflow-hidden shadow-sm">

            <div class="px-8 py-6 border-b border-slate-100 flex items-center justify-between bg-white">
                <div>
                    <h4 class="text-slate-800 font-bold text-lg">Listado de Métodos</h4>
                    <p class="text-slate-400 text-[10px] uppercase tracking-widest mt-1 font-semibold">
                        Configuración de tesorería
                    </p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-slate-400 text-[10px] uppercase tracking-[0.15em] bg-slate-50/50">
                            <th class="px-8 py-5 font-bold">Nombre del Método</th>
                            <th class="px-8 py-5 font-bold">Es Efectivo</th>
                            <th class="px-8 py-5 font-bold text-right">Acciones</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @forelse($paymentMethods as $method)
                            <tr class="hover:bg-slate-50/80 transition-all group">
                                <td class="px-8 py-5">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-9 h-9 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center border border-orange-100">
                                            <i class="fa-solid fa-credit-card text-xs"></i>
                                        </div>
                                        <p
                                            class="text-sm font-bold text-slate-700 group-hover:text-orange-600 transition-colors">
                                            {{ $method->name }}
                                        </p>
                                    </div>
                                </td>

                                <td class="px-8 py-5">
                                    @if ($method->is_efectivo)
                                        <span
                                            class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-100 text-emerald-700">
                                            Sí
                                        </span>
                                    @else
                                        <span
                                            class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-red-100 text-red-700">
                                            No
                                        </span>
                                    @endif
                                </td>

                                <td class="px-8 py-5">
                                    <div class="flex justify-end gap-2">
                                        @can('payment_methods.editar')
                                            <button wire:click="edit({{ $method->id }})"
                                                class="w-9 h-9 flex items-center justify-center rounded-xl bg-white text-slate-400 hover:text-amber-600 hover:border-amber-200 transition-all border border-slate-200 shadow-sm">
                                                <i class="fa-solid fa-pen text-[10px]"></i>
                                            </button>
                                        @endcan
                                        @can('payment_methods.eliminar')
                                            <button wire:click="deleteConfirm({{ $method->id }})"
                                                class="w-9 h-9 flex items-center justify-center rounded-xl bg-white text-slate-400 hover:text-rose-600 hover:border-rose-200 transition-all border border-slate-200 shadow-sm">
                                                <i class="fa-solid fa-trash-can text-[10px]"></i>
                                            </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="px-8 py-20 text-center">
                                    <div
                                        class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-50 mb-4 text-slate-200">
                                        <i class="fa-solid fa-wallet text-2xl"></i>
                                    </div>
                                    <p class="text-slate-400 text-sm font-medium italic">No hay métodos de pago
                                        registrados</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- PAGINACIÓN --}}
            <div class="px-8 py-5 border-t border-slate-100 bg-slate-50/30">
                {{ $paymentMethods->links() }}
            </div>
        </div>
    </div>

    {{-- MODAL --}}
    @if ($isOpen)
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center z-[100] p-4">
            <div class="bg-white rounded-[2rem] w-full max-w-md shadow-2xl overflow-hidden">

                <div class="px-8 py-6 bg-slate-50 border-b">
                    <h3 class="text-lg font-black text-slate-800">
                        {{ $payment_method_id ? 'Editar Método' : 'Nuevo Método de Pago' }}
                    </h3>
                    <p class="text-xs text-slate-500 font-medium">Complete la información del método</p>
                </div>

                <form wire:submit.prevent="store">
                    <div class="px-8 py-8 space-y-4">
                        <div>
                            <label class="text-[10px] font-bold uppercase text-slate-400 tracking-wider">Nombre del
                                Método</label>
                            <input wire:model="name" type="text" placeholder="Ej: Transferencia, Efectivo..."
                                class="w-full border-slate-200 rounded-xl px-4 py-3 text-sm focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 outline-none transition-all mt-1">
                            @error('name')
                                <span class="text-red-500 text-[10px] font-bold mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="flex items-center gap-2 mt-1">
                            <label class="relative inline-flex items-center cursor-pointer scale-90 origin-left">
                                <input type="checkbox" wire:model.live="is_efectivo" class="sr-only peer">
                                <div
                                    class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-orange-600">
                                </div>
                                <span
                                    class="ml-2 text-[10px] font-bold text-slate-400 peer-checked:text-orange-600 uppercase tracking-tighter">Es
                                    Efectivo</span>
                            </label>
                        </div>
                    </div>

                    <div class="px-8 py-6 bg-slate-50 flex justify-end gap-3">
                        <button type="button" wire:click="closeModal"
                            class="px-5 py-2.5 text-xs font-bold text-slate-500 hover:bg-slate-200 rounded-xl transition-colors">
                            Cancelar
                        </button>

                        <button type="submit"
                            class="px-6 py-2.5 bg-orange-600 hover:bg-orange-700 text-white text-xs font-bold rounded-xl shadow-lg shadow-orange-500/30 transition-all active:scale-95">
                            <i class="fa-solid fa-floppy-disk mr-2"></i> Guardar Método
                        </button>
                    </div>
                </form>

            </div>
        </div>
    @endif
</div>
