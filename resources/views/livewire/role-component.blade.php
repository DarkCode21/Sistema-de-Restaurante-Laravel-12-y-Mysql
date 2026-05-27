<div class="min-h-screen antialiased">
    <div class="max-w-7xl mx-auto">

        {{-- HEADER --}}
        <div class="flex flex-col md:flex-row gap-4 items-center justify-between mb-2">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Roles y Permisos</h1>
                <p class="text-slate-500 text-xs font-medium">Control de acceso y seguridad del sistema</p>
            </div>

            <div class="flex w-full md:w-auto gap-3">
                <div class="relative flex-grow md:w-72 group">
                    <i
                        class="fa-solid fa-magnifying-glass absolute left-4 top-3 text-slate-400 group-focus-within:text-orange-500 transition-colors"></i>
                    <input wire:model.live="search" type="text" placeholder="Buscar rol..."
                        class="w-full bg-white border border-slate-200 rounded-2xl pl-11 pr-4 py-2.5 text-sm shadow-sm focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 outline-none transition-all">
                </div>
                @can('roles.crear')
                    <button wire:click="create"
                        class="bg-orange-600 hover:bg-orange-700 text-white px-6 py-2.5 rounded-2xl text-xs font-bold transition-all shadow-md shadow-orange-100 flex items-center gap-2 active:scale-95">
                        <i class="fa-solid fa-plus"></i> Nuevo Rol
                    </button>
                @endcan
            </div>
        </div>

        {{-- TABLE CARD (ESTILO BLANCO) --}}
        <div class="bg-white border border-slate-200 overflow-hidden shadow-sm">

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-slate-400 text-[10px] uppercase tracking-[0.15em] bg-slate-50/50">
                            <th class="px-8 py-5 font-bold">Nombre del Rol</th>
                            <th class="px-8 py-5 font-bold">Permisos Asignados</th>
                            <th class="px-8 py-5 font-bold text-right">Acciones</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @forelse($roles as $role)
                            <tr class="hover:bg-slate-50/80 transition-all group">
                                <td class="px-8 py-5">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-500 group-hover:bg-orange-100 group-hover:text-orange-600 transition-colors">
                                            <i class="fa-solid fa-user-shield text-xs"></i>
                                        </div>
                                        <p class="text-sm font-bold text-slate-700">
                                            {{ $role->name }}
                                        </p>
                                    </div>
                                </td>

                                <td class="px-8 py-5">
                                    <div class="flex flex-wrap gap-1.5 max-w-md">
                                        @foreach ($role->permissions as $perm)
                                            <span
                                                class="px-2 py-0.5 text-[10px] font-medium rounded-md bg-orange-50 text-orange-600 border border-orange-100/50">
                                                {{ $perm->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>

                                <td class="px-8 py-5">
                                    <div class="flex justify-end gap-2">
                                        @can('roles.editar')
                                            <button wire:click="edit({{ $role->id }})"
                                                class="w-9 h-9 flex items-center justify-center rounded-xl bg-white text-slate-400 hover:text-orange-600 hover:border-orange-200 transition-all border border-slate-200 shadow-sm">
                                                <i class="fa-solid fa-pen text-[10px]"></i>
                                            </button>
                                        @endcan
                                        @can('roles.eliminar')
                                            <button wire:click="deleteConfirm({{ $role->id }})"
                                                class="w-9 h-9 flex items-center justify-center rounded-xl bg-white text-slate-400 hover:text-rose-600 hover:border-rose-200 transition-all border border-slate-200 shadow-sm">
                                                <i class="fa-solid fa-trash-can text-[10px]"></i>
                                            </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-8 py-20 text-center">
                                    <div
                                        class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-50 mb-4 text-slate-200">
                                        <i class="fa-solid fa-shield-halved text-2xl"></i>
                                    </div>
                                    <p class="text-slate-400 text-sm font-medium italic">No hay roles registrados</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-8 py-5 border-t border-slate-100 bg-slate-50/30">
                {{ $roles->links() }}
            </div>
        </div>
    </div>

    {{-- MODAL --}}
    @if ($isOpen)
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center z-[100] p-4">
            <div
                class="bg-white rounded-[2.5rem] w-full max-w-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh] animate-in fade-in zoom-in duration-300">

                {{-- HEADER (FIJO) --}}
                <div class="px-8 py-6 bg-slate-50 border-b flex-shrink-0 flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-black text-slate-800 tracking-tight">
                            {{ $role_id ? 'Editar Rol' : 'Nuevo Rol' }}
                        </h3>
                        <p class="text-xs text-slate-500 font-medium">Defina el nombre y los permisos de acceso</p>
                    </div>
                    <button wire:click="closeModal"
                        class="w-9 h-9 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-400 hover:bg-rose-50 hover:text-rose-500 transition-all shadow-sm">
                        <i class="fa-solid fa-xmark text-sm"></i>
                    </button>
                </div>

                {{-- FORMULARIO CON SCROLL --}}
                <form wire:submit.prevent="store" class="flex flex-col overflow-hidden">

                    <div class="px-8 py-8 space-y-8 overflow-y-auto custom-scrollbar">

                        {{-- Nombre del Rol --}}
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Nombre
                                del Rol</label>
                            <div class="relative mt-1">
                                <span class="absolute left-4 top-3.5 text-slate-400"><i
                                        class="fa-solid fa-shield-halved text-sm"></i></span>
                                <input wire:model="name" type="text" placeholder="Ej: Administrador, Cajero..."
                                    class="w-full bg-white border border-slate-200 rounded-xl pl-11 pr-4 py-3 text-sm text-slate-700 focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 outline-none transition-all shadow-sm">
                            </div>
                            @error('name')
                                <span class="text-rose-500 text-[10px] font-bold mt-1 block ml-1">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Listado de Permisos --}}
                        <div>
                            <label
                                class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1 mb-3 block">
                                Asignar Permisos al Rol
                            </label>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                @foreach ($permissions as $permission)
                                    <label
                                        class="group relative flex items-center gap-3 p-4 rounded-2xl border border-slate-100 bg-slate-50/50 cursor-pointer hover:bg-white hover:border-orange-300 hover:shadow-md transition-all">
                                        <div class="relative flex items-center">
                                            <input type="checkbox" wire:model="selectedPermissions"
                                                value="{{ $permission->name }}"
                                                class="w-5 h-5 rounded-lg border-slate-300 text-orange-600 focus:ring-orange-500/20 transition-all cursor-pointer">
                                        </div>
                                        <div class="flex flex-col">
                                            <span
                                                class="text-xs font-bold text-slate-700 group-hover:text-orange-600 transition-colors">
                                                {{ strtoupper(str_replace('.', ' ', $permission->name)) }}
                                            </span>
                                            <span class="text-[10px] text-slate-400 font-medium">Acceso a módulo</span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                    </div>

                    {{-- FOOTER (FIJO) --}}
                    <div class="px-8 py-6 bg-slate-50 flex justify-end gap-3 border-t flex-shrink-0">
                        <button type="button" wire:click="closeModal"
                            class="px-5 py-2.5 text-xs font-bold text-slate-500 hover:bg-slate-200 rounded-xl transition-colors">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="px-8 py-3 bg-orange-600 hover:bg-orange-700 text-white text-xs font-black rounded-xl shadow-lg shadow-orange-500/30 transition-all active:scale-95 uppercase tracking-widest">
                            <i class="fa-solid fa-floppy-disk mr-2"></i> Guardar Rol
                        </button>
                    </div>
                </form>

            </div>
        </div>
    @endif
</div>
