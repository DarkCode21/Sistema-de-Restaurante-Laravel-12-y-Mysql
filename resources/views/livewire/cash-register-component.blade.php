<div class="min-h-screen antialiased">
    <div class="max-w-7xl mx-auto">

        {{-- HEADER --}}
        <div class="flex flex-col md:flex-row gap-4 items-center justify-between mb-2">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Cajas de Efectivo</h1>
                <p class="text-slate-500 text-xs font-medium">Control de aperturas, cierres y flujos de efectivo</p>
            </div>

            <div class="flex w-full md:w-auto gap-3">
                <div class="relative flex-grow md:w-72 group">
                    <i
                        class="fa-solid fa-magnifying-glass absolute left-4 top-3 text-slate-400 group-focus-within:text-blue-500 transition-colors"></i>
                    <input wire:model.live="search" type="text" placeholder="Buscar caja..."
                        class="w-full bg-white border border-slate-200 rounded-2xl pl-11 pr-4 py-2.5 text-sm shadow-sm focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all">
                </div>
                @can('cajas.crear')
                    <button wire:click="create"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-2xl text-xs font-bold transition-all flex items-center gap-2 active:scale-95 shadow-md shadow-blue-100">
                        <i class="fa-solid fa-cash-register"></i> Abrir Caja
                    </button>
                @endcan
            </div>
        </div>

        {{-- TABLE CARD (ESTILO BLANCO) --}}
        <div class="bg-white border border-slate-200 rounded-[2rem] overflow-hidden shadow-sm">

            <div class="px-8 py-6 border-b border-slate-100 bg-white">
                <h4 class="text-slate-800 font-bold text-lg">Historial de Cajas</h4>
                <p class="text-slate-400 text-[10px] uppercase tracking-widest mt-1 font-semibold">Registro de
                    movimientos de tesorería</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-slate-400 text-[10px] uppercase tracking-[0.15em] bg-slate-50/50">
                            <th class="px-8 py-5 font-bold">Caja / Responsable</th>
                            <th class="px-8 py-5 font-bold text-center">Estado</th>
                            <th class="px-8 py-5 font-bold">Monto Apertura</th>
                            <th class="px-8 py-5 font-bold">Saldo Actual</th>
                            <th class="px-8 py-5 font-bold text-right">Acciones</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @forelse($registers as $reg)
                            <tr class="hover:bg-slate-50/80 transition-all group">
                                <td class="px-8 py-5">
                                    <p
                                        class="text-sm font-bold text-slate-700 group-hover:text-blue-600 transition-colors">
                                         {{ $reg->terminal?->name ?? $reg->name }}</p>
                                    <p class="text-[10px] text-slate-400 font-medium italic">Abierto por:
                                         {{ $reg->opener->name ?? 'N/A' }} · {{ $reg->opened_at?->format('d/m/Y H:i') }}</p>
                                </td>
                                <td class="px-8 py-5 text-center">
                                    <span
                                        class="px-3 py-1 text-[9px] font-black uppercase rounded-lg {{ $reg->status === 'open' ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-slate-100 text-slate-500 border border-slate-200' }}">
                                        {{ $reg->status === 'open' ? 'ABIERTA' : 'CERRADA' }}
                                    </span>
                                </td>
                                <td class="px-8 py-5 text-sm text-slate-600">
                                    <span
                                        class="font-medium text-slate-400 text-xs">{{ $empresa->currency_simbol }}</span>
                                    {{ number_format($reg->opening_amount, 2) }}
                                </td>
                                <td class="px-8 py-5 text-sm font-bold text-slate-700">
                                    <span class="text-blue-600">{{ $empresa->currency_simbol }}</span>
                                    {{ number_format($reg->current_amount, 2) }}
                                </td>
                                <td class="px-8 py-5 text-right">
                                    <div class="flex justify-end gap-2">
                                        @can('cajas.movimientos')
                                            {{-- BOTÓN: VER MOVIMIENTOS (SIEMPRE VISIBLE) --}}
                                            <a href="{{ route('boxes.movements', encrypt($reg->id)) }}"
                                                class="w-9 h-9 flex items-center justify-center rounded-xl bg-white text-slate-400 hover:text-orange-600 hover:border-orange-200 transition-all border border-slate-200 shadow-sm"
                                                title="Ver movimientos detallados">
                                                <i class="fa-solid fa-eye text-[10px]"></i>
                                            </a>
                                        @endcan

                                        @if ($reg->status === 'closed')
                                            {{-- ESTADO CERRADO --}}
                                            <div class="flex items-center gap-2 px-3 py-1 bg-slate-100 text-slate-400 rounded-xl border border-slate-200 cursor-not-allowed"
                                                title="Caja ya cerrada">
                                                <i class="fa-solid fa-lock text-[10px]"></i>
                                                <span class="text-[9px] font-black uppercase">Cerrada</span>
                                            </div>
                                        @else
                                            @can('cajas.editar')
                                                {{-- ACCIONES PARA CAJA ABIERTA --}}
                                                <button wire:click="edit({{ $reg->id }})"
                                                    class="w-9 h-9 flex items-center justify-center rounded-xl bg-white text-slate-400 hover:text-blue-600 hover:border-blue-200 transition-all border border-slate-200 shadow-sm"
                                                    title="Editar apertura">
                                                    <i class="fa-solid fa-pen-to-square text-[10px]"></i>
                                                </button>
                                            @endcan
                                            @can('cajas.eliminar')
                                                <button wire:click="deleteConfirm({{ $reg->id }})"
                                                    class="w-9 h-9 flex items-center justify-center rounded-xl bg-white text-slate-400 hover:text-rose-600 hover:border-rose-200 transition-all border border-slate-200 shadow-sm"
                                                    title="Eliminar registro">
                                                    <i class="fa-solid fa-trash text-[10px]"></i>
                                                </button>
                                            @endcan
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-8 py-20 text-center">
                                    <div
                                        class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-50 mb-4 text-slate-200">
                                        <i class="fa-solid fa-cash-register text-2xl"></i>
                                    </div>
                                    <p class="text-slate-400 text-sm font-medium italic">No se encontraron registros de
                                        caja.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- PAGINACIÓN --}}
            <div class="px-8 py-5 border-t border-slate-100 bg-slate-50/30">
                {{ $registers->links() }}
            </div>
        </div>
    </div>

    {{-- MODAL --}}
    @if ($isOpen)
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center z-[100] p-4">
            <div class="bg-white rounded-[2rem] w-full max-w-md shadow-2xl overflow-hidden">

                <!-- Cabecera del Modal -->
                <div class="px-8 py-6 bg-slate-50 border-b">
                    <h3 class="text-lg font-black text-slate-800">
                        {{ $cash_register_id ? 'Editar Apertura' : 'Apertura de Caja' }}
                    </h3>
                    <p class="text-xs text-slate-500 font-medium">Complete la información para continuar</p>
                </div>

                <!-- Formulario -->
                <form wire:submit.prevent="store">
                    <div class="px-8 py-8 space-y-4">

                        @if ($cash_register_id)
                            <div>
                                <label class="text-[10px] font-bold uppercase text-slate-400 tracking-wider">Caja física</label>
                                <div class="mt-1 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">{{ $terminals->firstWhere('id', $terminal_id)?->name }}</div>
                            </div>
                        @else
                            <div>
                                <label class="text-[10px] font-bold uppercase text-slate-400 tracking-wider">Caja física</label>
                                <select wire:model="terminal_id" class="mt-1 w-full rounded-xl border-slate-200 px-4 py-3 text-sm focus:border-orange-500 focus:ring-orange-500">
                                    @foreach ($terminals as $terminal)
                                        <option value="{{ $terminal->id }}">{{ $terminal->name }}</option>
                                    @endforeach
                                </select>
                                @error('terminal_id') <span class="mt-1 block text-[10px] font-bold text-red-500">{{ $message }}</span> @enderror
                                <button type="button" wire:click="$toggle('showTerminalForm')" class="mt-2 text-[10px] font-bold text-slate-400 hover:text-orange-600">{{ $showTerminalForm ? 'Cancelar nueva caja' : '+ Agregar otra caja física' }}</button>
                                @if ($showTerminalForm)
                                    <div class="mt-2 flex gap-2">
                                        <input wire:model="new_terminal_name" type="text" placeholder="Ej: Caja terraza" class="min-w-0 flex-1 rounded-lg border-slate-200 px-3 py-2 text-xs focus:border-orange-500 focus:ring-orange-500">
                                        <button type="button" wire:click="addTerminal" class="rounded-lg bg-slate-100 px-3 text-[10px] font-bold text-slate-700 hover:bg-slate-200">Agregar</button>
                                    </div>
                                    @error('new_terminal_name') <span class="mt-1 block text-[10px] font-bold text-red-500">{{ $message }}</span> @enderror
                                @endif
                            </div>
                        @endif

                        <!-- Monto Inicial -->
                        <div>
                            <label class="text-[10px] font-bold uppercase text-slate-400 tracking-wider">
                                Monto Inicial
                            </label>
                            <div class="relative mt-1">
                                <span class="absolute left-4 top-3 text-slate-400 text-sm font-medium">
                                    {{ $empresa->currency_simbol }}
                                </span>
                                <input wire:model="opening_amount" type="number" step="0.01" placeholder="0.00"
                                    class="w-full border-slate-200 rounded-xl pl-10 pr-4 py-3 text-sm focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 outline-none transition-all">
                            </div>
                            @error('opening_amount')
                                <span class="text-red-500 text-[10px] font-bold mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Notas / Observaciones -->
                        <div>
                            <label class="text-[10px] font-bold uppercase text-slate-400 tracking-wider">
                                Notas / Observaciones
                            </label>
                            <textarea wire:model="notes" rows="2" placeholder="Alguna observación inicial..."
                                class="w-full border-slate-200 rounded-xl px-4 py-3 text-sm focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 outline-none transition-all mt-1"></textarea>
                            @error('notes')
                                <span class="text-red-500 text-[10px] font-bold mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>

                    </div>

                    <!-- Pie del Modal / Botones -->
                    <div class="px-8 py-6 bg-slate-50 flex justify-end gap-3">
                        <button type="button" wire:click="closeModal"
                            class="px-5 py-2.5 text-xs font-bold text-slate-500 hover:bg-slate-200 rounded-xl transition-colors">
                            Cancelar
                        </button>

                        <button type="submit"
                            class="px-6 py-2.5 bg-orange-600 hover:bg-orange-700 text-white text-xs font-bold rounded-xl shadow-lg shadow-orange-500/30 transition-all active:scale-95">
                            <i class="fa-solid fa-floppy-disk mr-2"></i>
                            {{ $cash_register_id ? 'Actualizar' : 'Confirmar Apertura' }}
                        </button>
                    </div>
                </form>

            </div>
        </div>
    @endif
</div>
