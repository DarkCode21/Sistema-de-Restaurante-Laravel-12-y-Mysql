<div class="min-h-screen antialiased">
    <div class="max-w-7xl mx-auto">

        {{-- HEADER --}}
        <div class="flex flex-col md:flex-row gap-4 items-center justify-between mb-2">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Productos</h1>
                <p class="text-slate-500 text-xs font-medium">Gestiona el inventario y catálogo de productos</p>
            </div>

            <div class="flex w-full md:w-auto gap-3">
                <div class="relative flex-grow md:w-72 group">
                    <i
                        class="fa-solid fa-magnifying-glass absolute left-4 top-3 text-slate-400 group-focus-within:text-orange-500 transition-colors"></i>
                    <input wire:model.live="search" type="text" placeholder="Buscar producto..."
                        class="w-full bg-white border border-slate-200 rounded-2xl pl-11 pr-4 py-2.5 text-sm shadow-sm focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 outline-none transition-all">
                </div>

                @can('productos.crear')
                    <button wire:click="create"
                        class="bg-orange-600 hover:bg-orange-700 text-white px-6 py-2.5 rounded-2xl text-xs font-bold transition-all shadow-md shadow-orange-100 flex items-center gap-2 active:scale-95">
                        <i class="fa-solid fa-plus"></i> Nuevo Producto
                    </button>
                @endcan

            </div>
        </div>

        {{-- TABLE CARD --}}
        <div class="bg-white border border-slate-200 overflow-hidden shadow-sm">

            <div class="px-8 py-6 border-b border-slate-100 flex items-center justify-between bg-white">
                <div>
                    <h4 class="text-slate-800 font-bold text-lg">Listado de Productos</h4>
                    <p class="text-slate-400 text-[10px] uppercase tracking-widest mt-1 font-semibold">
                        Control de Existencias
                    </p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-slate-400 text-[10px] uppercase tracking-[0.15em] bg-slate-50/50">
                            <th class="px-8 py-5 font-bold">Producto</th>
                            <th class="px-8 py-5 font-bold">Categoría</th>
                            <th class="px-8 py-5 font-bold">Precio</th>
                            <th class="px-8 py-5 font-bold">Stock</th>
                            <th class="px-8 py-5 font-bold">Estado</th>
                            <th class="px-8 py-5 font-bold text-right">Acciones</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @forelse($products as $product)
                            <tr class="hover:bg-slate-50/80 transition-all group">
                                <td class="px-8 py-5">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-9 h-9 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center border border-orange-100">
                                            <i class="fa-solid fa-box text-xs"></i>
                                        </div>
                                        <p
                                            class="text-sm font-bold text-slate-700 group-hover:text-orange-600 transition-colors">
                                            {{ $product->name }}
                                        </p>
                                    </div>
                                </td>
                                <td class="px-8 py-5">
                                    <span
                                        class="text-xs font-semibold text-slate-500 bg-slate-100 px-3 py-1 rounded-lg">
                                        {{ $product->category->name }}
                                    </span>
                                </td>
                                <td class="px-8 py-5 text-sm font-bold text-slate-700">
                                    {{ $empresa->currency_simbol }}{{ number_format($product->price, 2) }}
                                </td>
                                <td class="px-8 py-5">
                                    <span
                                        class="text-sm font-bold {{ $product->stock <= 5 ? 'text-rose-600' : 'text-slate-600' }}">
                                        {{ $product->stock }}
                                    </span>
                                </td>
                                <td class="px-8 py-5">
                                    @if ($product->status)
                                        <span
                                            class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                            <span class="w-1.5 h-1.5 rounded-full bg-orange-500"></span> Activo
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-full text-xs font-medium bg-slate-100 text-slate-500">
                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Inactivo
                                        </span>
                                    @endif
                                </td>

                                <td class="px-8 py-5">
                                    <div class="flex justify-end gap-2">
                                        @can('productos.editar')
                                            <button wire:click="edit({{ $product->id }})"
                                                class="w-9 h-9 flex items-center justify-center rounded-xl bg-white text-slate-400 hover:text-amber-600 hover:border-amber-200 transition-all border border-slate-200 shadow-sm">
                                                <i class="fa-solid fa-pen text-[10px]"></i>
                                            </button>
                                        @endcan
                                        @can('productos.eliminar')
                                            <button wire:click="deleteConfirm({{ $product->id }})"
                                                class="w-9 h-9 flex items-center justify-center rounded-xl bg-white text-slate-400 hover:text-rose-600 hover:border-rose-200 transition-all border border-slate-200 shadow-sm">
                                                <i class="fa-solid fa-trash-can text-[10px]"></i>
                                            </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-8 py-20 text-center">
                                    <div
                                        class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-50 mb-4 text-slate-200">
                                        <i class="fa-solid fa-boxes-stacked text-2xl"></i>
                                    </div>
                                    <p class="text-slate-400 text-sm font-medium italic">No hay productos registrados
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-8 py-5 border-t border-slate-100 bg-slate-50/30">
                {{ $products->links() }}
            </div>
        </div>
    </div>

    {{-- MODAL --}}
    @if ($isOpen)
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center z-[100] p-4">
            <div class="bg-white rounded-[2rem] w-full max-w-lg shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">

                {{-- HEADER (FIJO) --}}
                <div class="px-8 py-6 bg-slate-50 border-b flex-shrink-0">
                    <h3 class="text-lg font-black text-slate-800">
                        {{ $product_id ? 'Editar Producto' : 'Nuevo Producto' }}
                    </h3>
                    <p class="text-xs text-slate-500 font-medium">Complete los detalles del artículo</p>
                </div>

                {{-- FORMULARIO CON SCROLL --}}
                <form wire:submit.prevent="store" class="flex flex-col overflow-hidden">

                    <div class="px-8 py-8 grid grid-cols-1 md:grid-cols-2 gap-6 overflow-y-auto custom-scrollbar">

                        <div
                            class="md:col-span-2 flex flex-col items-center justify-center border-2 border-dashed border-slate-200 rounded-[2rem] p-6 bg-slate-50/50 group hover:border-orange-400 transition-all">
                            @if ($image)
                                <img src="{{ $image->temporaryUrl() }}"
                                    class="w-40 h-40 object-cover rounded-2xl shadow-md mb-4">
                            @elseif($old_image)
                                <img src="{{ asset('storage/' . $old_image) }}"
                                    class="w-40 h-40 object-cover rounded-2xl shadow-md mb-4">
                            @else
                                <div
                                    class="w-20 h-20 bg-white rounded-2xl flex items-center justify-center text-slate-300 mb-4 shadow-sm">
                                    <i class="fa-solid fa-camera text-3xl"></i>
                                </div>
                            @endif

                            <label class="relative cursor-pointer">
                                <span
                                    class="bg-white border border-slate-200 px-4 py-2 rounded-xl text-xs font-bold text-slate-600 shadow-sm hover:bg-slate-50 transition-all">
                                    {{ $old_image || $image ? 'Cambiar Foto del Plato' : 'Subir Foto del Plato' }}
                                </span>
                                <input type="file" wire:model="image" class="hidden" accept="image/*">
                            </label>

                            <div wire:loading wire:target="image" class="mt-2 text-[10px] font-bold text-orange-600">
                                <i class="fa-solid fa-spinner animate-spin mr-1"></i> Cargando vista previa...
                            </div>
                            @error('image')
                                <span class="text-red-500 text-[10px] font-bold mt-2">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="text-[10px] font-bold uppercase text-slate-400 tracking-wider">Nombre del
                                Plato/Bebida</label>
                            <input wire:model="name" type="text" placeholder="Ej: Ceviche Carretillero..."
                                class="w-full border-slate-200 rounded-xl px-4 py-3 text-sm focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 outline-none transition-all mt-1">
                            @error('name')
                                <span class="text-red-500 text-[10px] font-bold mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label
                                class="text-[10px] font-bold uppercase text-slate-400 tracking-wider">Categoría</label>
                            <select wire:model="category_id"
                                class="w-full border-slate-200 rounded-xl px-4 py-3 text-sm focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 outline-none transition-all mt-1">
                                <option value="">Seleccionar...</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                            @error('category_id')
                                <span class="text-red-500 text-[10px] font-bold mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label class="text-[10px] font-bold uppercase text-slate-400 tracking-wider">Precio de
                                Venta</label>
                            <div class="relative">
                                <span class="absolute left-4 top-4 text-slate-400 text-sm">S/</span>
                                <input wire:model="price" type="number" step="0.01" placeholder="0.00"
                                    class="w-full border-slate-200 rounded-xl pl-10 pr-4 py-3 text-sm focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 outline-none transition-all mt-1">
                            </div>
                            @error('price')
                                <span class="text-red-500 text-[10px] font-bold mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label
                                class="text-[10px] font-bold uppercase text-slate-400 tracking-wider">Disponibilidad</label>
                            <select wire:model="status"
                                class="w-full border-slate-200 rounded-xl px-4 py-3 text-sm focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 outline-none transition-all mt-1">
                                <option value="1">Hay en carta</option>
                                <option value="0">Agotado</option>
                            </select>
                        </div>

                        <div>
                            <label class="text-[10px] font-bold uppercase text-slate-400 tracking-wider">Porciones
                                Disponibles</label>
                            <input wire:model="stock" type="number" placeholder="Ej: 50"
                                class="w-full border-slate-200 rounded-xl px-4 py-3 text-sm focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 outline-none transition-all mt-1">
                            @error('stock')
                                <span class="text-red-500 text-[10px] font-bold mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="md:col-span-2 bg-slate-50 p-4 rounded-2xl border border-slate-100">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-10 h-10 bg-orange-100 rounded-xl flex items-center justify-center text-orange-600">
                                        <i class="fa-solid fa-fire-burner"></i>
                                    </div>
                                    <div>
                                        <h4 class="text-xs font-bold text-slate-700">Preparación en Cocina</h4>
                                        <p class="text-[10px] text-slate-500">¿Este producto requiere orden de
                                            preparación?</p>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" wire:model="requires_kitchen" class="sr-only peer">
                                    <div
                                        class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-600">
                                    </div>
                                </label>
                            </div>
                        </div>

                    </div>

                    <div class="px-8 py-6 bg-slate-50 flex justify-end gap-3 border-t flex-shrink-0">
                        <button type="button" wire:click="closeModal"
                            class="px-5 py-2.5 text-xs font-bold text-slate-500 hover:bg-slate-200 rounded-xl transition-colors">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="px-6 py-2.5 bg-orange-600 hover:bg-orange-700 text-white text-xs font-bold rounded-xl shadow-lg shadow-orange-500/30 transition-all active:scale-95">
                            <i class="fa-solid fa-floppy-disk mr-2"></i> Guardar en Menú
                        </button>
                    </div>

                </form>
            </div>
        </div>
    @endif
</div>
