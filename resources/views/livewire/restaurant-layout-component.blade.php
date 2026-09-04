<div class="mx-auto max-w-5xl p-4 sm:p-6">
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <p class="text-[10px] font-black uppercase tracking-[0.18em] text-orange-600">Configuración del salón</p>
            <h1 class="mt-1 text-xl font-black text-slate-900">Pisos y zonas</h1>
        </div>
        <a href="{{ route('tables.index') }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-[10px] font-black uppercase text-slate-600 hover:border-slate-300">Volver a mesas</a>
    </div>

    <div class="grid gap-5 lg:grid-cols-2">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-black text-slate-800">Nuevo piso</h2>
            <form wire:submit="addFloor" class="mt-4 flex gap-2">
                <input wire:model="floor_name" placeholder="Ej: Segundo piso" class="min-w-0 flex-1 rounded-lg border-slate-200 text-sm focus:border-orange-500 focus:ring-orange-500">
                <button class="rounded-lg bg-slate-900 px-4 text-[10px] font-black uppercase text-white hover:bg-slate-700">Agregar</button>
            </form>
            @error('floor_name') <p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p> @enderror
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-black text-slate-800">Nueva zona</h2>
            <form wire:submit="addArea" class="mt-4 grid gap-3 sm:grid-cols-2">
                <select wire:model="restaurant_floor_id" class="rounded-lg border-slate-200 text-sm focus:border-orange-500 focus:ring-orange-500">
                    <option value="">Seleccionar piso</option>
                    @foreach ($floors as $floor)<option value="{{ $floor->id }}">{{ $floor->name }}</option>@endforeach
                </select>
                <select wire:model="area_type" class="rounded-lg border-slate-200 text-sm focus:border-orange-500 focus:ring-orange-500">
                    <option value="salon">Salón</option><option value="terraza">Terraza</option><option value="barra">Barra</option><option value="privado">Privado</option>
                </select>
                <input wire:model="area_name" placeholder="Ej: Terraza" class="sm:col-span-2 rounded-lg border-slate-200 text-sm focus:border-orange-500 focus:ring-orange-500">
                <button class="sm:col-span-2 rounded-lg bg-orange-600 py-2.5 text-[10px] font-black uppercase text-white hover:bg-orange-700">Agregar zona</button>
            </form>
            @error('restaurant_floor_id') <p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p> @enderror
            @error('area_name') <p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p> @enderror
        </section>
    </div>

    <section class="mt-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-sm font-black text-slate-800">Ambientes registrados</h2>
        <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($floors as $floor)
                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="font-black text-slate-700">{{ $floor->name }}</p>
                    <p class="mt-2 text-xs font-bold text-slate-400">{{ $floor->areas->pluck('name')->join(' · ') ?: 'Sin zonas' }}</p>
                </div>
            @endforeach
        </div>
    </section>
</div>
