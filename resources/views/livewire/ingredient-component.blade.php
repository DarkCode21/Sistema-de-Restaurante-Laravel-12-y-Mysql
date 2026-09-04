<div class="mx-auto max-w-6xl space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-slate-800">Insumos</h1>
            <p class="text-xs font-medium text-slate-500">Stock base para recetas y producción.</p>
        </div>
        <div class="flex gap-2">
            <input wire:model.live="search" type="search" placeholder="Buscar insumo..." class="rounded-xl border-slate-200 px-3 py-2 text-sm">
            <button wire:click="create" class="rounded-xl bg-orange-600 px-4 py-2 text-xs font-black uppercase text-white hover:bg-orange-700">Nuevo</button>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-[10px] font-black uppercase tracking-wider text-slate-400">
                <tr><th class="px-5 py-4">Insumo</th><th class="px-5 py-4">Stock</th><th class="px-5 py-4">Mínimo</th><th class="px-5 py-4">Costo prom.</th><th class="px-5 py-4 text-right">Acciones</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($ingredients as $ingredient)
                    <tr>
                        <td class="px-5 py-4 font-bold text-slate-700">{{ $ingredient->name }}</td>
                        <td class="px-5 py-4 font-black {{ $ingredient->stock <= $ingredient->minimum_stock ? 'text-rose-600' : 'text-slate-700' }}">{{ rtrim(rtrim(number_format($ingredient->stock, 3, '.', ''), '0'), '.') }} {{ $ingredient->unit }}</td>
                        <td class="px-5 py-4 text-slate-500">{{ rtrim(rtrim(number_format($ingredient->minimum_stock, 3, '.', ''), '0'), '.') }} {{ $ingredient->unit }}</td>
                        <td class="px-5 py-4 font-semibold text-slate-600">{{ $ingredient->unit_cost === null ? 'Sin costo' : 'S/ ' . number_format($ingredient->unit_cost, 2) }}</td>
                        <td class="px-5 py-4">
                            <div class="flex justify-end gap-2">
                                <button wire:click="edit({{ $ingredient->id }})" class="w-9 h-9 flex items-center justify-center rounded-xl bg-white text-slate-400 hover:text-amber-600 hover:border-amber-200 transition-all border border-slate-200 shadow-sm"><i class="fa-solid fa-pen text-[10px]"></i></button>
                                <button wire:click="deleteConfirm({{ $ingredient->id }})" class="w-9 h-9 flex items-center justify-center rounded-xl bg-white text-slate-400 hover:text-rose-600 hover:border-rose-200 transition-all border border-slate-200 shadow-sm"><i class="fa-solid fa-trash-can text-[10px]"></i></button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-12 text-center text-sm text-slate-400">Aún no hay insumos.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t border-slate-100 px-5 py-3">{{ $ingredients->links() }}</div>
    </div>

    @if ($isOpen)
        <div class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/60 p-4">
            <form wire:submit="store" class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
                <div class="mb-5 flex items-center justify-between"><h2 class="text-lg font-black text-slate-800">{{ $ingredient_id ? 'Editar insumo' : 'Nuevo insumo' }}</h2><button type="button" wire:click="closeModal" class="text-slate-400"><i class="fa-solid fa-xmark"></i></button></div>
                <div class="space-y-4">
                    <div><label class="text-xs font-bold text-slate-500">Nombre</label><input wire:model="name" class="mt-1 w-full rounded-xl border-slate-200 px-3 py-2 text-sm"><x-input-error :messages="$errors->get('name')" class="mt-1" /></div>
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <div><label class="text-xs font-bold text-slate-500">Unidad</label><select wire:model="unit" class="mt-1 w-full rounded-xl border-slate-200 px-3 py-2 text-sm">@foreach ($units as $value)<option value="{{ $value }}">{{ $value }}</option>@endforeach</select></div>
                        <div><label class="text-xs font-bold text-slate-500">Stock</label><input wire:model="stock" type="number" min="0" step="0.001" class="mt-1 w-full rounded-xl border-slate-200 px-3 py-2 text-sm"></div>
                        <div><label class="text-xs font-bold text-slate-500">Mínimo</label><input wire:model="minimum_stock" type="number" min="0" step="0.001" class="mt-1 w-full rounded-xl border-slate-200 px-3 py-2 text-sm"></div>
                        <div><label class="text-xs font-bold text-slate-500">Costo inicial</label><input wire:model="unit_cost" type="number" min="0" step="0.01" placeholder="0.00" class="mt-1 w-full rounded-xl border-slate-200 px-3 py-2 text-sm"></div>
                    </div>
                    <x-input-error :messages="$errors->get('stock')" /><x-input-error :messages="$errors->get('minimum_stock')" /><x-input-error :messages="$errors->get('unit_cost')" />
                    <button class="w-full rounded-xl bg-orange-600 py-3 text-xs font-black uppercase text-white hover:bg-orange-700">Guardar</button>
                </div>
            </form>
        </div>
    @endif
</div>
