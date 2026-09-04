<div class="min-h-screen antialiased">
    <div class="max-w-7xl mx-auto">

        <div class="flex flex-col md:flex-row gap-4 items-center justify-between mb-2">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Categorías</h1>
            </div>

            <div class="flex w-full md:w-auto gap-3">
                <div class="relative flex-grow md:w-72 group">
                    <i
                        class="fa-solid fa-magnifying-glass absolute left-4 top-3 text-slate-400 group-focus-within:text-orange-500 transition-colors"></i>
                    <input wire:model.live="search" type="text" placeholder="Buscar categoría..."
                        class="w-full bg-white border border-slate-200 rounded-2xl pl-11 pr-4 py-2.5 text-sm shadow-sm focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 outline-none transition-all">
                </div>
                @can('categorias.crear')
                    <button wire:click="create"
                        class="bg-orange-600 hover:bg-orange-700 text-white px-6 py-2.5 rounded-2xl text-xs font-bold transition-all shadow-md shadow-orange-100 flex items-center gap-2 active:scale-95">
                        <i class="fa-solid fa-plus"></i> Nueva Categoría
                    </button>
                @endcan
            </div>
        </div>

        <div class="bg-white border border-slate-200 overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-slate-400 text-[10px] uppercase tracking-[0.15em] bg-slate-50/50">
                            <th class="px-8 py-5 font-bold">Nombre</th>
                            <th class="px-8 py-5 font-bold text-right">Acciones</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @forelse($categories as $category)
                            <tr class="hover:bg-slate-50/80 transition-all group">
                                <td class="px-8 py-5">
                                    <p
                                        class="text-sm font-bold text-slate-700 group-hover:text-orange-600 transition-colors">
                                        {{ $category->name }}
                                    </p>
                                </td>
                                <td class="px-8 py-5">
                                    <div class="flex justify-end gap-2">
                                        @can('categorias.editar')
                                            <button wire:click="edit({{ $category->id }})"
                                                class="w-9 h-9 flex items-center justify-center rounded-xl bg-white text-slate-400 hover:text-amber-600 hover:border-amber-200 transition-all border border-slate-200 shadow-sm">
                                                <i class="fa-solid fa-pen text-[10px]"></i>
                                            </button>
                                        @endcan
                                        @can('categorias.eliminar')
                                            <button wire:click="deleteConfirm({{ $category->id }})"
                                                class="w-9 h-9 flex items-center justify-center rounded-xl bg-white text-slate-400 hover:text-rose-600 hover:border-rose-200 transition-all border border-slate-200 shadow-sm">
                                                <i class="fa-solid fa-trash-can text-[10px]"></i>
                                            </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-8 py-20 text-center">
                                    <div
                                        class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-50 mb-4">
                                        <i class="fa-solid fa-tags text-slate-200 text-xl"></i>
                                    </div>
                                    <p class="text-slate-400 text-sm font-medium italic">No hay categorías registradas</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-8 py-5 border-t border-slate-100 bg-slate-50/30">
                {{ $categories->links() }}
            </div>
        </div>
    </div>

    @if ($isOpen)
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center z-[100] p-4">
            <div class="bg-white rounded-[2rem] w-full max-w-md shadow-2xl overflow-hidden">

                <div class="px-8 py-5 border-b">
                    <h3 class="text-lg font-black">
                        {{ $category_id ? 'Editar Categoría' : 'Nueva Categoría' }}
                    </h3>
                </div>

                <form wire:submit.prevent="store">
                    <div class="px-8 py-6 space-y-5">

                        <div>
                            <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1.5">Nombre</label>
                            <input wire:model="name" type="text" placeholder="Ej. Ceviches"
                                class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 transition-all">
                            @error('name')
                                <span class="text-red-500 text-xs font-semibold mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>

                    </div>

                    <div class="px-8 py-5 bg-slate-50/50 border-t border-slate-100 flex justify-end gap-3">
                        <button type="button" wire:click="closeModal" 
                            class="px-5 py-2.5 text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl transition-colors">
                            Cancelar
                        </button>

                        <button type="submit" 
                            class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition-colors shadow-md shadow-emerald-100">
                            Guardar
                        </button>
                    </div>
                </form>

            </div>
        </div>
    @endif
</div>
