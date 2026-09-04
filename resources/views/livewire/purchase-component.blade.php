<div class="mx-auto max-w-6xl space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-slate-800">Compras e inventario</h1>
            <p class="text-xs font-medium text-slate-500">Cada compra actualiza stock y costo promedio ponderado.</p>
        </div>
        <div class="flex gap-2">
            <button wire:click="loadLowStockItems" class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-2 text-xs font-black uppercase text-amber-700 hover:bg-amber-100">
                <i class="fa-solid fa-wand-magic-sparkles mr-1"></i> Sugerir reposición
            </button>
            <button wire:click="$set('isSupplierOpen', true)" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-black uppercase text-slate-600 hover:bg-slate-50">
                <i class="fa-solid fa-truck mr-1"></i> Nuevo proveedor
            </button>
        </div>
    </div>

    <form wire:submit="store" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="grid gap-4 sm:grid-cols-3">
            <div><label class="text-xs font-bold text-slate-500">Proveedor</label><select wire:model="supplier_id" class="mt-1 w-full rounded-xl border-slate-200 px-3 py-2 text-sm"><option value="">Sin proveedor</option>@foreach ($suppliers as $supplier)<option value="{{ $supplier->id }}">{{ $supplier->name }}</option>@endforeach</select><x-input-error :messages="$errors->get('supplier_id')" class="mt-1" /></div>
            <div><label class="text-xs font-bold text-slate-500">Comprobante o referencia</label><input wire:model="reference" class="mt-1 w-full rounded-xl border-slate-200 px-3 py-2 text-sm" placeholder="Factura, boleta..."><x-input-error :messages="$errors->get('reference')" class="mt-1" /></div>
            <div><label class="text-xs font-bold text-slate-500">Fecha</label><input wire:model="purchased_at" type="date" class="mt-1 w-full rounded-xl border-slate-200 px-3 py-2 text-sm"><x-input-error :messages="$errors->get('purchased_at')" class="mt-1" /></div>
        </div>

        <div class="mt-5 space-y-2">
            @foreach ($items as $index => $item)
                <div wire:key="purchase-item-{{ $index }}" class="grid grid-cols-[1fr_6rem_7rem_auto] gap-2">
                    <select wire:model="items.{{ $index }}.ingredient_id" class="rounded-xl border-slate-200 px-3 py-2 text-sm"><option value="">Insumo...</option>@foreach ($ingredients as $ingredient)<option value="{{ $ingredient->id }}">{{ $ingredient->name }} ({{ $ingredient->unit }})</option>@endforeach</select>
                    <input wire:model="items.{{ $index }}.quantity" type="number" min="0.001" step="0.001" placeholder="Cant." class="rounded-xl border-slate-200 px-3 py-2 text-sm" aria-label="Cantidad">
                    <input wire:model="items.{{ $index }}.unit_cost" type="number" min="0" step="0.01" placeholder="Costo S/ 0.00" class="rounded-xl border-slate-200 px-3 py-2 text-sm" aria-label="Costo unitario">
                    <button type="button" wire:click="removeItem({{ $index }})" class="px-2 text-rose-600" aria-label="Eliminar insumo"><i class="fa-solid fa-trash-can"></i></button>
                </div>
                <x-input-error :messages="$errors->get('items.' . $index . '.ingredient_id')" /><x-input-error :messages="$errors->get('items.' . $index . '.quantity')" /><x-input-error :messages="$errors->get('items.' . $index . '.unit_cost')" />
            @endforeach
        </div>
        <div class="mt-4 flex justify-between gap-3">
            <button type="button" wire:click="addItem" class="text-xs font-black uppercase text-orange-600 hover:text-orange-700"><i class="fa-solid fa-plus mr-1"></i> Agregar insumo</button>
            <button class="rounded-xl bg-orange-600 px-5 py-2 text-xs font-black uppercase text-white hover:bg-orange-700">Registrar compra</button>
        </div>
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4"><h2 class="font-black text-slate-800">Últimas compras</h2><input wire:model.live="search" type="search" placeholder="Buscar proveedor o referencia..." class="rounded-xl border-slate-200 px-3 py-2 text-xs"></div>
        <div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="bg-slate-50 text-[10px] font-black uppercase tracking-wider text-slate-400"><tr><th class="px-5 py-3">Fecha</th><th class="px-5 py-3">Proveedor</th><th class="px-5 py-3">Detalle</th><th class="px-5 py-3 text-right">Total</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse ($purchases as $purchase)<tr><td class="px-5 py-4 text-slate-500">{{ $purchase->purchased_at->format('d/m/Y') }}</td><td class="px-5 py-4 font-bold text-slate-700">{{ $purchase->supplier?->name ?? 'Sin proveedor' }}<div class="text-[10px] font-normal text-slate-400">{{ $purchase->reference }}</div></td><td class="px-5 py-4 text-xs text-slate-500">{{ $purchase->details->map(fn ($detail) => $detail->ingredient->name . ' x' . rtrim(rtrim(number_format($detail->quantity, 3, '.', ''), '0'), '.'))->join(', ') }}</td><td class="px-5 py-4 text-right font-black text-slate-700">S/ {{ number_format($purchase->total, 2) }}</td></tr>@empty<tr><td colspan="4" class="px-5 py-10 text-center text-sm text-slate-400">Aún no hay compras.</td></tr>@endforelse</tbody></table></div>
        <div class="border-t border-slate-100 px-5 py-3">{{ $purchases->links() }}</div>
    </div>

    @if ($isSupplierOpen)
        <div class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/60 p-4"><form wire:submit="saveSupplier" class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl"><div class="mb-4 flex items-center justify-between"><h2 class="font-black text-slate-800">Nuevo proveedor</h2><button type="button" wire:click="$set('isSupplierOpen', false)" class="text-slate-400"><i class="fa-solid fa-xmark"></i></button></div><div class="space-y-3"><div><label class="text-xs font-bold text-slate-500">Nombre</label><input wire:model="supplier_name" class="mt-1 w-full rounded-xl border-slate-200 px-3 py-2 text-sm"><x-input-error :messages="$errors->get('supplier_name')" class="mt-1" /></div><div><label class="text-xs font-bold text-slate-500">Contacto</label><input wire:model="supplier_contact_name" class="mt-1 w-full rounded-xl border-slate-200 px-3 py-2 text-sm"></div><div><label class="text-xs font-bold text-slate-500">Teléfono</label><input wire:model="supplier_phone" class="mt-1 w-full rounded-xl border-slate-200 px-3 py-2 text-sm"></div><div><label class="text-xs font-bold text-slate-500">RUC/DNI</label><input wire:model="supplier_document_number" class="mt-1 w-full rounded-xl border-slate-200 px-3 py-2 text-sm"></div><button class="w-full rounded-xl bg-orange-600 py-3 text-xs font-black uppercase text-white hover:bg-orange-700">Guardar proveedor</button></div></form></div>
    @endif
</div>
