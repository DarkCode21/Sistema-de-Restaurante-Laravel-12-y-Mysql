<div class="mx-auto max-w-5xl">
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-800">Estaciones de preparación</h1>
            <p class="text-xs font-semibold text-slate-500">Asigna productos y personal a Parrilla, Cocina, Bar u otras áreas.</p>
        </div>
        <button wire:click="create" class="rounded-xl bg-orange-600 px-4 py-2.5 text-xs font-black uppercase text-white hover:bg-orange-700">
            Nueva estación
        </button>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="w-full text-left">
            <thead class="bg-slate-50 text-[10px] font-black uppercase tracking-widest text-slate-400">
                <tr>
                    <th class="px-5 py-4">Estación</th>
                    <th class="px-5 py-4">Equipo</th>
                    <th class="px-5 py-4 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
                @forelse ($stations as $station)
                    <tr>
                        <td class="px-5 py-4 font-bold text-slate-700">{{ $station->name }}</td>
                        <td class="px-5 py-4 text-slate-500">{{ $station->users->pluck('name')->join(', ') ?: 'Sin personal asignado' }}</td>
                        <td class="px-5 py-4 text-right">
                            <button wire:click="edit({{ $station->id }})" class="mr-2 text-amber-600"><i class="fa-solid fa-pen"></i></button>
                            <button wire:click="deleteConfirm({{ $station->id }})" class="text-rose-600"><i class="fa-solid fa-trash-can"></i></button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-5 py-12 text-center text-sm text-slate-400">Crea las estaciones antes de asignarlas a productos.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t border-slate-100 px-5 py-3">{{ $stations->links() }}</div>
    </div>

    @if ($isOpen)
        <div class="fixed inset-0 z-[110] flex items-center justify-center bg-slate-900/60 p-4">
            <form wire:submit="store" class="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl">
                <h2 class="text-lg font-black text-slate-800">{{ $station_id ? 'Editar estación' : 'Nueva estación' }}</h2>
                <label class="mt-5 block text-[10px] font-black uppercase tracking-wide text-slate-400">Nombre</label>
                <input wire:model="name" type="text" placeholder="Ej.: Parrilla" class="mt-1 w-full rounded-xl border-slate-200 px-3 py-2.5 text-sm">
                @error('name') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror

                <p class="mt-5 text-[10px] font-black uppercase tracking-wide text-slate-400">Personal de preparación</p>
                <div class="mt-2 max-h-44 space-y-2 overflow-y-auto rounded-xl bg-slate-50 p-3">
                    @forelse ($cooks as $cook)
                        <label class="flex items-center gap-2 text-sm font-semibold text-slate-600">
                            <input wire:model="user_ids" type="checkbox" value="{{ $cook->id }}" class="rounded border-slate-300 text-orange-600">
                            {{ $cook->name }}
                        </label>
                    @empty
                        <p class="text-xs text-slate-400">No hay usuarios disponibles para asignar.</p>
                    @endforelse
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" wire:click="$set('isOpen', false)" class="px-4 py-2 text-xs font-black uppercase text-slate-500">Cancelar</button>
                    <button type="submit" class="rounded-xl bg-orange-600 px-4 py-2 text-xs font-black uppercase text-white">Guardar</button>
                </div>
            </form>
        </div>
    @endif
</div>
