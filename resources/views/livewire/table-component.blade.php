<div class="min-h-screen bg-slate-50">
    <main class="mx-auto max-w-[1720px] p-3 sm:p-6">
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <nav class="flex items-center gap-7 border-b border-slate-100 px-6 sm:px-8" aria-label="Vista de mesas">
                <button wire:click="closeLayoutEditor" class="border-b-2 py-4 text-[11px] font-black {{ !$layoutEditor ? 'border-orange-500 text-slate-900' : 'border-transparent text-slate-400 hover:text-slate-700' }}">
                    Estado en tiempo real
                </button>
                @can('mesas.editar')
                    <button wire:click="openLayoutEditor" class="border-b-2 py-4 text-[11px] font-black {{ $layoutEditor ? 'border-orange-500 text-slate-900' : 'border-transparent text-slate-400 hover:text-slate-700' }}">
                        Configuración de mesas
                    </button>
                @endcan
            </nav>

            <div class="flex flex-col gap-3 px-5 py-4 sm:px-8 lg:flex-row lg:items-center lg:justify-between">
                <label class="relative block w-full max-w-xs">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-[11px] text-slate-400"></i>
                    <input wire:model.live.debounce.300ms="search" type="search" placeholder="Buscar mesa o cliente" class="w-full rounded-lg border-slate-200 py-2 pl-8 pr-3 text-xs text-slate-700 placeholder:text-slate-400 focus:border-orange-500 focus:ring-orange-500">
                </label>

                <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                    <label class="relative">
                        <i class="fa-solid fa-building absolute left-3 top-1/2 -translate-y-1/2 text-[10px] text-slate-400"></i>
                        <select wire:model.live="selectedFloorId" aria-label="Piso" class="w-full appearance-none rounded-lg border-slate-200 py-2 pl-8 pr-7 text-xs font-bold text-slate-600 focus:border-orange-500 focus:ring-orange-500">
                            @foreach ($floors as $floor)
                                <option value="{{ $floor->id }}">{{ $floor->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="relative">
                        <i class="fa-solid fa-layer-group absolute left-3 top-1/2 -translate-y-1/2 text-[10px] text-slate-400"></i>
                        <select wire:model.live="selectedAreaId" aria-label="Zona" class="w-full appearance-none rounded-lg border-slate-200 py-2 pl-8 pr-7 text-xs font-bold text-slate-600 focus:border-orange-500 focus:ring-orange-500">
                            <option value="">Todas las zonas</option>
                            @foreach ($areas as $area)
                                <option value="{{ $area->id }}">{{ $area->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="relative">
                        <i class="fa-solid fa-list-check absolute left-3 top-1/2 -translate-y-1/2 text-[10px] text-slate-400"></i>
                        <select wire:model.live="statusFilter" aria-label="Estado" class="w-full appearance-none rounded-lg border-slate-200 py-2 pl-8 pr-7 text-xs font-bold text-slate-600 focus:border-orange-500 focus:ring-orange-500">
                            <option value="">Todos los estados</option>
                            <option value="libre">Disponible</option>
                            <option value="ocupada">Ocupada</option>
                            <option value="reservada">Reservada</option>
                        </select>
                    </label>
                </div>
            </div>

            @if ($layoutEditor)
                <section class="border-y border-slate-100 bg-slate-50 px-5 py-4 sm:px-8">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <p class="text-xs font-semibold text-slate-500"><i class="fa-solid fa-arrows-up-down-left-right mr-1.5 text-orange-500"></i>Arrastra las mesas libremente y guarda al terminar.</p>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" data-save-layout class="rounded-lg bg-slate-900 px-3 py-2 text-[10px] font-black uppercase tracking-wide text-white hover:bg-slate-700">Guardar distribución</button>
                            @can('mesas.crear')
                                <button wire:click="create" class="rounded-lg bg-orange-600 px-3 py-2 text-[10px] font-black uppercase tracking-wide text-white hover:bg-orange-700">+ Mesa</button>
                            @endcan
                        </div>
                    </div>
                </section>
            @endif

            <div data-floor-scroll class="restaurant-floor-scroll border-t border-slate-50">
                <div id="floor-canvas" data-layout-editor="{{ $layoutEditor ? 'true' : 'false' }}" class="restaurant-floor-canvas">
                    @forelse ($tables as $table)
                        <x-restaurant-table :table="$table" :order="$table->orders->first()" :configuration="$layoutEditor" :selected="(int) $selectedTableId === $table->id" />
                    @empty
                        <div class="absolute inset-0 grid place-items-center text-center">
                            <div>
                                <i class="fa-solid fa-chair text-3xl text-slate-200"></i>
                                <p class="mt-3 text-sm font-bold text-slate-400">No hay mesas con estos filtros.</p>
                            </div>
                        </div>
                    @endforelse
                    @if (!$layoutEditor && $selectedTable)
                        @php
                            $selectedOrder = $selectedTable->orders->first();
                            $selectedReady = $selectedOrder?->is_ready_for_checkout && !$selectedOrder?->sale()->exists();
                            $selectedActionX = min((int) $selectedTable->x_pos + 20, 980);
                            $selectedActionY = (int) $selectedTable->y_pos + (int) $selectedTable->table_height + 36;
                            $selectedActionY = $selectedActionY > 780 ? max(0, (int) $selectedTable->y_pos - 42) : $selectedActionY;
                        @endphp
                        <aside class="restaurant-table-actions" style="--restaurant-table-action-x: {{ $selectedActionX }}px; --restaurant-table-action-y: {{ $selectedActionY }}px;">
                            <span class="restaurant-table-actions__name">{{ $selectedTable->name }}</span>
                            @can('ordenes.crear')
                                <a href="{{ route('orders.create', encrypt($selectedTable->id)) }}" class="restaurant-table-actions__primary">{{ $selectedOrder ? 'Gestionar' : 'Atender' }}</a>
                            @endcan
                            @can('ordenes.cobrar')
                                @if ($selectedReady)
                                    <a href="{{ route('orders.cashier', ['order' => $selectedOrder->id, 'quick_checkout' => 1]) }}" class="restaurant-table-actions__checkout">Cobrar</a>
                                @endif
                            @endcan
                        </aside>
                    @endif
                </div>
            </div>

        </section>
    </main>

    @if ($isOpen)
        @php
            $previewTable = \App\Models\Table::make([
                'name' => $name ?: 'T1',
                'status' => $status,
                'shape' => $shape,
                'table_width' => $table_width,
                'table_height' => $table_height,
                'orientation' => $orientation,
                'capacity' => $capacity ?: 4,
            ]);
        @endphp
        <div class="fixed inset-0 z-[100] grid place-items-center p-4">
            <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" wire:click="closeModal"></div>
            <div class="relative max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl">
                <div class="mb-6 flex items-start justify-between gap-4">
                    <div><p class="text-[10px] font-black uppercase tracking-[0.2em] text-orange-600">Configuración de mesa</p><h2 class="mt-1 text-xl font-black text-slate-900">{{ $table_id ? 'Editar mesa' : 'Nueva mesa' }}</h2></div>
                    <button wire:click="closeModal" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100"><i class="fa-solid fa-xmark"></i></button>
                </div>

                <div class="grid gap-8 lg:grid-cols-[1fr_280px]">
                    <form wire:submit="store" class="space-y-4">
                        <div class="grid grid-cols-2 gap-3">
                            <div><label class="mb-1 block text-[10px] font-black uppercase tracking-wide text-slate-400">Nombre</label><input wire:model.live="name" type="text" placeholder="Mesa 12" class="w-full rounded-lg border-slate-200 text-sm focus:border-orange-500 focus:ring-orange-500">@error('name') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror</div>
                            <div><label class="mb-1 block text-[10px] font-black uppercase tracking-wide text-slate-400">Capacidad</label><input wire:model.live="capacity" type="number" min="1" max="99" class="w-full rounded-lg border-slate-200 text-sm focus:border-orange-500 focus:ring-orange-500">@error('capacity') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror</div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div><label class="mb-1 block text-[10px] font-black uppercase tracking-wide text-slate-400">Piso</label><select wire:model.live="restaurant_floor_id" class="w-full rounded-lg border-slate-200 text-sm focus:border-orange-500 focus:ring-orange-500"><option value="">Seleccionar</option>@foreach ($floors as $floor)<option value="{{ $floor->id }}">{{ $floor->name }}</option>@endforeach</select>@error('restaurant_floor_id') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror</div>
                            <div><label class="mb-1 block text-[10px] font-black uppercase tracking-wide text-slate-400">Zona</label><select wire:model="dining_area_id" class="w-full rounded-lg border-slate-200 text-sm focus:border-orange-500 focus:ring-orange-500"><option value="">Seleccionar</option>@foreach ($tableAreas as $area)<option value="{{ $area->id }}">{{ $area->name }}</option>@endforeach</select>@error('dining_area_id') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror</div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div><label class="mb-1 block text-[10px] font-black uppercase tracking-wide text-slate-400">Forma</label><select wire:model.live="shape" class="w-full rounded-lg border-slate-200 text-sm focus:border-orange-500 focus:ring-orange-500"><option value="round">Redonda</option><option value="square">Cuadrada</option><option value="rectangle">Rectangular</option></select></div>
                            @if ($shape === 'rectangle')
                                <div><label class="mb-1 block text-[10px] font-black uppercase tracking-wide text-slate-400">Orientación</label><select wire:model.live="orientation" class="w-full rounded-lg border-slate-200 text-sm focus:border-orange-500 focus:ring-orange-500"><option value="horizontal">Horizontal</option><option value="vertical">Vertical</option></select></div>
                            @else
                                <div><label class="mb-1 block text-[10px] font-black uppercase tracking-wide text-slate-400">Orientación</label><div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-400">Automática</div></div>
                            @endif
                        </div>
                        <div class="w-full sm:w-1/2"><label class="mb-1 block text-[10px] font-black uppercase tracking-wide text-slate-400">Estado</label><select wire:model.live="status" class="w-full rounded-lg border-slate-200 text-sm focus:border-orange-500 focus:ring-orange-500"><option value="libre">Disponible</option><option value="ocupada">Ocupada</option><option value="reservada">Reservada</option></select></div>
                        <button type="button" wire:click="$toggle('showPhysicalDimensions')" class="text-[10px] font-bold text-slate-400 hover:text-slate-700">{{ $showPhysicalDimensions ? 'Ocultar medidas avanzadas' : 'Ajustar medidas avanzadas' }}</button>
                        @if ($showPhysicalDimensions)
                            <div class="grid grid-cols-2 gap-3">
                                <div><label class="mb-1 block text-[10px] font-black uppercase tracking-wide text-slate-400">Ancho físico</label><input wire:model.live="table_width" type="number" min="80" max="360" step="10" class="w-full rounded-lg border-slate-200 text-sm focus:border-orange-500 focus:ring-orange-500">@error('table_width') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror</div>
                                <div><label class="mb-1 block text-[10px] font-black uppercase tracking-wide text-slate-400">Alto físico</label><input wire:model.live="table_height" type="number" min="80" max="360" step="10" class="w-full rounded-lg border-slate-200 text-sm focus:border-orange-500 focus:ring-orange-500">@error('table_height') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror</div>
                            </div>
                        @endif
                        @error('layout') <span class="block text-xs font-bold text-red-600">{{ $message }}</span> @enderror
                        <button class="w-full rounded-xl bg-slate-900 py-3 text-[11px] font-black uppercase tracking-[0.18em] text-white hover:bg-orange-600">Guardar mesa</button>
                    </form>

                    <aside class="rounded-xl bg-slate-50 p-4">
                        <p class="text-[10px] font-black uppercase tracking-[0.16em] text-slate-400">Vista previa</p>
                        <div class="mt-5 flex min-h-[260px] items-center justify-center overflow-hidden rounded-lg bg-white">
                            <x-restaurant-table :table="$previewTable" :width="$table_width" :height="$table_height" :capacity="$capacity" :orientation="$orientation" preview />
                        </div>
                        <p class="mt-3 text-center text-[10px] font-semibold text-slate-400">{{ $table_width }} × {{ $table_height }} · {{ $capacity ?: 0 }} PAX</p>
                    </aside>
                </div>
            </div>
        </div>
    @endif

    <script src="https://cdn.jsdelivr.net/npm/interactjs/dist/interact.min.js"></script>
    <script>
        document.addEventListener('livewire:init', () => {
            if (window.restaurantTableInteractionsReady) return;

            window.restaurantTableInteractionsReady = true;
            interact('.draggable-table').draggable({
                allowFrom: '.restaurant-table__body, .drag-handle',
                modifiers: [interact.modifiers.restrictRect({ restriction: '#floor-canvas', endOnly: true })],
                listeners: {
                    move(event) {
                        const target = event.target;
                        const x = (parseFloat(target.dataset.x) || 0) + event.dx;
                        const y = (parseFloat(target.dataset.y) || 0) + event.dy;
                        target.style.setProperty('--restaurant-table-x', `${x}px`);
                        target.style.setProperty('--restaurant-table-y', `${y}px`);
                        target.dataset.x = x;
                        target.dataset.y = y;
                    },
                },
            });

            document.addEventListener('click', async (event) => {
                if (!event.target.closest('[data-save-layout]')) return;

                const positions = [...document.querySelectorAll('.draggable-table')].map((table) => ({
                    id: table.dataset.id,
                    x: table.dataset.x,
                    y: table.dataset.y,
                }));
                const result = await @this.savePositions(positions);

                result.positions.forEach((position) => {
                    const table = document.querySelector(`.draggable-table[data-id="${position.id}"]`);
                    if (!table) return;
                    table.dataset.x = position.x;
                    table.dataset.y = position.y;
                    table.style.setProperty('--restaurant-table-x', `${position.x}px`);
                    table.style.setProperty('--restaurant-table-y', `${position.y}px`);
                });
            });
        });
    </script>
</div>
