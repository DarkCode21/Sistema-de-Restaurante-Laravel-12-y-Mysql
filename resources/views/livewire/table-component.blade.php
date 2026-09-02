<div class="min-h-screen">
    <div class="max-w-[1800px] mx-auto space-y-4">

        {{-- HEADER: ALTA DENSIDAD / MINIMALISTA --}}
        <header
            class="flex items-center justify-between bg-white border border-slate-200 px-4 sm:px-6 py-4 rounded-xl shadow-sm">
            <div class="flex items-center gap-5">
                <div class="flex flex-col leading-tight">
                    <h1 class="text-lg font-black tracking-tighter text-slate-900 uppercase">
                        Distribución <span class="text-orange-600">de Salones</span>
                    </h1>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button type="button" onclick="toggleRestaurantFullscreen()" data-fullscreen-toggle
                    class="w-10 h-10 inline-flex items-center justify-center rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 transition-colors"
                    title="Pantalla completa" aria-label="Pantalla completa">
                    <i class="fa-solid fa-expand"></i>
                </button>
                @can('mesas.crear')
                    <button wire:click="create"
                        class="px-5 py-2.5 bg-slate-900 hover:bg-orange-600 text-white text-[11px] font-black rounded-lg transition-all active:scale-95 shadow-lg shadow-slate-200 uppercase tracking-widest">
                        + Añadir
                    </button>
                @endcan
            </div>

        </header>

        <p class="px-1 text-xs font-semibold text-slate-500 sm:hidden">
            <i class="fa-solid fa-arrows-left-right mr-1 text-orange-600"></i> Desliza el plano para ver todas las mesas.
        </p>

        <div data-floor-scroll class="overflow-x-auto touch-pan-x overscroll-x-contain rounded-xl pb-2">
            <div id="floor-canvas"
                class="relative min-w-[1200px] min-h-[820px] bg-slate-50 rounded-xl border border-slate-200 shadow-[inset_0_2px_10px_rgba(0,0,0,0.05)] overflow-hidden transition-all">

            <div class="absolute inset-0 opacity-[0.2]"
                style="background-image: radial-gradient(#64748b 1px, transparent 1px); background-size: 40px 40px;">
            </div>

            <div class="relative w-full h-full p-10">
                @forelse($tables as $table)
                    <div wire:key="table-{{ $table->id }}" data-id="{{ $table->id }}" data-x="{{ $table->x_pos }}"
                        data-y="{{ $table->y_pos }}" class="draggable-table absolute group active:z-50"
                        style="transform: translate({{ $table->x_pos }}px, {{ $table->y_pos }}px);">

                        <div class="relative w-48 h-fit flex flex-col items-center">

                            <div class="relative w-40 h-40 flex items-center justify-center">
                                <img src="{{ asset('images/table.jpg') }}"
                                    class="w-36 h-36 object-contain transition-all duration-200 cursor-default md:cursor-grab md:active:cursor-grabbing select-none drop-shadow-[0_20px_20px_rgba(0,0,0,0.2)] group-hover:scale-105"
                                    style="mix-blend-mode: multiply;" alt="Mesa">

                                <div class="absolute top-2 right-2 z-10">
                                    <span class="relative flex h-5 w-5">
                                        @if ($table->status === 'ocupada')
                                            <span
                                                class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                            <span
                                                class="relative inline-flex rounded-full h-5 w-5 bg-red-500 border-2 border-white shadow-md"></span>
                                        @elseif ($table->status === 'reservada')
                                            <span
                                                class="animate-pulse absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                                            <span
                                                class="relative inline-flex rounded-full h-5 w-5 bg-amber-500 border-2 border-white shadow-md"></span>
                                        @else
                                            <span
                                                class="relative inline-flex rounded-full h-5 w-5 bg-emerald-500 border-2 border-white shadow-md"></span>
                                        @endif
                                    </span>
                                </div>
                            </div>

                            <div
                                class="w-full mt-2 bg-white border border-slate-200 rounded-xl p-3 shadow-[0_10px_25px_-5px_rgba(0,0,0,0.1)] group-hover:border-orange-400 transition-all">
                                <div class="flex justify-between items-start mb-3">
                                    <div class="flex flex-col">
                                        <span
                                            class="text-[13px] font-black text-slate-800 uppercase tracking-tighter leading-none">{{ $table->name }}</span>
                                        <span class="text-[10px] font-bold text-slate-400 mt-1">{{ $table->capacity }}
                                            PAX</span>
                                    </div>
                                    <div class="flex gap-1">
                                        @can('mesas.editar')
                                            <button wire:click="edit({{ $table->id }})"
                                                class="p-1 text-slate-400 hover:text-orange-600 transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path
                                                        d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"
                                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            </button>
                                        @endcan
                                        @can('mesas.eliminar')
                                            <button wire:click="deleteConfirm({{ $table->id }})"
                                                class="p-1 text-slate-400 hover:text-red-600 transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            </button>
                                        @endcan
                                    </div>
                                </div>
                                @can('ordenes.crear')
                                    @php($openOrder = $table->orders->first())
                                    <div class="grid grid-cols-1 gap-2">
                                        <a href="{{ route('orders.create', encrypt($table->id)) }}"
                                            class="flex items-center justify-center py-2 bg-orange-600 text-white text-[10px] font-bold uppercase rounded-lg hover:bg-orange-700 transition-all shadow-sm">
                                            Gestionar
                                        </a>

                                        @can('ordenes.cobrar')
                                            @if ($openOrder?->is_ready_for_checkout)
                                                <a href="{{ route('orders.cashier', ['order' => $openOrder->id, 'quick_checkout' => 1]) }}"
                                                    class="flex items-center justify-center gap-1.5 py-2 bg-emerald-600 text-white text-[10px] font-black uppercase rounded-lg hover:bg-emerald-700 transition-all shadow-sm">
                                                    <i class="fa-solid fa-cash-register"></i>
                                                    Cobrar y liberar mesa
                                                </a>
                                            @endif
                                        @endcan
                                    </div>
                                @endcan
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="absolute inset-0 flex items-center justify-center opacity-10 pointer-events-none">
                        <span class="text-5xl font-black uppercase tracking-[0.5em] text-slate-900 text-center">
                            No hay mesas
                        </span>
                    </div>
                @endforelse
            </div>
            </div>
        </div>
    </div>

    {{-- MODAL PRO: COMPACTO Y SIN DISTRACCIONES --}}
    @if ($isOpen)
        <div class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" wire:click="closeModal"></div>
            <div
                class="relative bg-white border border-slate-200 rounded-2xl w-full max-w-md shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-150">
                <div class="p-8">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h2 class="text-xl font-black text-slate-900 tracking-tight">Propiedades</h2>
                            <p class="text-[10px] font-bold text-orange-600 uppercase tracking-widest">Asset
                                Configuration
                            </p>
                        </div>
                        <button wire:click="closeModal"
                            class="text-slate-400 hover:text-slate-900 uppercase text-[10px] font-black tracking-widest">Cerrar</button>
                    </div>

                    <form wire:submit.prevent="store" class="space-y-6">
                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Etiqueta
                                de Mesa</label>
                            <input wire:model="name" type="text" placeholder="ID-001"
                                class="w-full bg-slate-50 border border-slate-200 focus:border-orange-500 rounded-xl px-4 py-3 text-sm font-bold outline-none transition-all">
                            @error('name')
                                <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <label
                                    class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Capacidad</label>
                                <input wire:model="capacity" type="number"
                                    class="w-full bg-slate-50 border border-slate-200 focus:border-orange-500 rounded-xl px-4 py-3 text-sm font-bold outline-none">
                                @error('capacity')
                                    <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="space-y-1">
                                <label
                                    class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Estado</label>
                                <select wire:model="status"
                                    class="w-full bg-slate-50 border border-slate-200 focus:border-orange-500 rounded-xl px-4 py-3 text-sm font-bold outline-none appearance-none">
                                    <option value="libre">Disponible</option>
                                    <option value="ocupada">Ocupada</option>
                                    <option value="reservada">Reservada</option>
                                </select>
                                @error('status')
                                    <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <button type="submit"
                            class="w-full py-4 bg-slate-900 hover:bg-orange-600 text-white text-[11px] font-black rounded-xl transition-all uppercase tracking-[0.2em] shadow-lg shadow-slate-200">
                            Actualizar Nodo
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <script src="https://cdn.jsdelivr.net/npm/interactjs/dist/interact.min.js"></script>
    <script>
        function toggleRestaurantFullscreen() {
            if (document.fullscreenElement) {
                document.exitFullscreen?.();
                return;
            }

            document.documentElement.requestFullscreen?.().catch(() => {});
        }

        document.addEventListener('livewire:init', function() {
            if (!window.matchMedia('(min-width: 768px)').matches) {
                return;
            }

            interact('.draggable-table').draggable({
                inertia: true,
                modifiers: [
                    interact.modifiers.restrictRect({
                        restriction: '#floor-canvas',
                        endOnly: true
                    })
                ],
                listeners: {
                    move(event) {
                        var target = event.target;
                        var x = (parseFloat(target.getAttribute('data-x')) || 0) + event.dx;
                        var y = (parseFloat(target.getAttribute('data-y')) || 0) + event.dy;
                        target.style.transform = 'translate(' + x + 'px, ' + y + 'px)';
                        target.setAttribute('data-x', x);
                        target.setAttribute('data-y', y);
                    },
                    end(event) {
                        const id = event.target.getAttribute('data-id');
                        const x = event.target.getAttribute('data-x');
                        const y = event.target.getAttribute('data-y');
                        @this.updatePosition(id, x, y);
                    }
                }
            });
        });
    </script>
</div>
