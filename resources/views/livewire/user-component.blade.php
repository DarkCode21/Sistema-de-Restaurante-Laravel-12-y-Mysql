<div class="min-h-screen antialiased">
    <div class="max-w-7xl mx-auto space-y-6">

        {{-- HEADER FUERA DE LA CARD --}}
        <div class="flex flex-col md:flex-row gap-4 items-center justify-between mb-2">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Gestión de Usuarios</h1>
                <p class="text-slate-500 text-xs font-medium">Administra los permisos y perfiles de acceso</p>
            </div>

            <div class="flex w-full md:w-auto gap-3">
                <div class="relative flex-grow md:w-72 group">
                    <i
                        class="fa-solid fa-magnifying-glass absolute left-4 top-3 text-slate-400 group-focus-within:text-orange-500 transition-colors"></i>
                    <input wire:model.live="search" type="text" placeholder="Buscar usuario..."
                        class="w-full bg-white border border-slate-200 rounded-2xl pl-11 pr-4 py-2.5 text-sm shadow-sm focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 outline-none transition-all">
                </div>
                <button wire:click="create"
                    class="bg-orange-600 hover:bg-orange-700 text-white px-6 py-2.5 rounded-2xl text-xs font-bold transition-all shadow-md shadow-orange-200 flex items-center gap-2 active:scale-95">
                    <i class="fa-solid fa-plus"></i> Nuevo Usuario
                </button>
            </div>
        </div>

        {{-- TABLE CARD (AHORA EN BLANCO) --}}
        <div class="bg-white border border-slate-200 overflow-hidden shadow-sm">

            {{-- HEADER INTERNO DE LA TABLA --}}
            <div class="px-8 py-6 border-b border-slate-100 flex items-center justify-between bg-white">
                <div>
                    <h4 class="text-slate-800 font-bold text-lg">Registros del Sistema</h4>
                    <p class="text-slate-400 text-[10px] uppercase tracking-widest mt-1 font-semibold">Lista oficial de
                        miembros</p>
                </div>
                <button
                    class="px-4 py-2 bg-white hover:bg-slate-50 text-slate-600 text-[10px] font-bold rounded-xl transition-colors border border-slate-200 flex items-center gap-2 shadow-sm">
                    <i class="fa-solid fa-download text-orange-500"></i> Exportar
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-slate-400 text-[10px] uppercase tracking-[0.15em] bg-slate-50/50">
                            <th class="px-8 py-5 font-bold">Información Personal</th>
                            <th class="px-8 py-5 font-bold text-center">Estado de Cuenta</th>
                            <th class="px-8 py-5 font-bold text-center">Rol</th>
                            <th class="px-8 py-5 font-bold text-right">Herramientas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($users as $user)
                            <tr class="hover:bg-slate-50/80 transition-all group">
                                <td class="px-8 py-5">
                                    <div class="flex items-center gap-4">
                                        <div class="relative">
                                            <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=EEF2FF&color=6366f1"
                                                class="w-10 h-10 rounded-2xl border border-slate-200 shadow-sm"
                                                alt="">
                                            <div
                                                class="absolute -bottom-1 -right-1 w-3.5 h-3.5 bg-emerald-500 border-2 border-white rounded-full shadow-sm">
                                            </div>
                                        </div>
                                        <div>
                                            <p
                                                class="text-sm font-bold text-slate-700 group-hover:text-orange-600 transition-colors">
                                                {{ $user->name }}</p>
                                            <p class="text-[11px] text-slate-400 font-medium">{{ $user->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-5 text-center">
                                    <span
                                        class="px-3 py-1 text-[9px] font-black uppercase rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100">
                                        Verificado
                                    </span>
                                </td>
                                <td class="px-8 py-5 text-center">
                                    @php
                                        $role = $user->roles->first()?->name;
                                    @endphp

                                    @if ($role)
                                        <span
                                            class="px-3 py-1 text-[9px] font-black uppercase rounded-lg 
                                            bg-orange-50 text-orange-600 border border-orange-100">
                                            {{ $role }}
                                        </span>
                                    @else
                                        <span
                                            class="px-3 py-1 text-[9px] font-black uppercase rounded-lg 
                                            bg-slate-100 text-slate-400 border border-slate-200">
                                            Sin rol
                                        </span>
                                    @endif
                                </td>
                                <td class="px-8 py-5">
                                    <div class="flex justify-end gap-2">
                                        <button wire:click="edit({{ $user->id }})"
                                            class="w-9 h-9 flex items-center justify-center rounded-xl bg-white text-slate-400 hover:text-amber-600 hover:border-amber-200 transition-all border border-slate-200 shadow-sm">
                                            <i class="fa-solid fa-pen text-[10px]"></i>
                                        </button>
                                        <button wire:click="deleteConfirm({{ $user->id }})"
                                            class="w-9 h-9 flex items-center justify-center rounded-xl bg-white text-slate-400 hover:text-rose-600 hover:border-rose-200 transition-all border border-slate-200 shadow-sm">
                                            <i class="fa-solid fa-trash-can text-[10px]"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-8 py-20 text-center">
                                    <div
                                        class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-50 mb-4">
                                        <i class="fa-solid fa-inbox text-slate-300 text-xl"></i>
                                    </div>
                                    <p class="text-slate-400 text-sm font-medium italic">No se encontraron registros.
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- PAGINACIÓN --}}
            <div class="px-8 py-5 border-t border-slate-100 bg-slate-50/30">
                {{ $users->links() }}
            </div>
        </div>
    </div>

    {{-- MODAL --}}
    @if ($isOpen)
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center z-[100] p-4">
            <div
                class="bg-white rounded-[2.5rem] w-full max-w-lg shadow-2xl overflow-hidden flex flex-col max-h-[90vh] animate-in fade-in zoom-in duration-300">

                {{-- HEADER (FIJO) --}}
                <div class="px-8 py-6 bg-slate-50 border-b flex-shrink-0 flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-black text-slate-800 tracking-tight">
                            {{ $user_id ? 'Editar' : 'Nuevo' }} Usuario
                        </h3>
                        <p class="text-xs text-slate-500 font-medium">Gestión de credenciales de acceso</p>
                    </div>
                    <button wire:click="closeModal"
                        class="w-9 h-9 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-400 hover:bg-rose-50 hover:text-rose-500 transition-all shadow-sm">
                        <i class="fa-solid fa-xmark text-sm"></i>
                    </button>
                </div>

                {{-- FORMULARIO CON SCROLL --}}
                <form wire:submit.prevent="store" class="flex flex-col overflow-hidden">

                    <div class="px-8 py-8 grid grid-cols-1 md:grid-cols-2 gap-6 overflow-y-auto custom-scrollbar">

                        {{-- Nombre --}}
                        <div class="md:col-span-2">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Nombre
                                Completo</label>
                            <div class="relative mt-1">
                                <span class="absolute left-4 top-3.5 text-slate-400"><i
                                        class="fa-solid fa-user text-sm"></i></span>
                                <input wire:model="name" type="text" placeholder="Ej. Alex Morgan"
                                    class="w-full bg-white border border-slate-200 rounded-xl pl-11 pr-4 py-3 text-sm text-slate-700 focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 outline-none transition-all shadow-sm">
                            </div>
                            @error('name')
                                <span class="text-rose-500 text-[10px] font-bold mt-1 block ml-1">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Correo --}}
                        <div class="md:col-span-2">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Correo
                                Electrónico</label>
                            <div class="relative mt-1">
                                <span class="absolute left-4 top-3.5 text-slate-400"><i
                                        class="fa-solid fa-envelope text-sm"></i></span>
                                <input wire:model="email" type="email" placeholder="correo@ejemplo.com"
                                    class="w-full bg-white border border-slate-200 rounded-xl pl-11 pr-4 py-3 text-sm text-slate-700 focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 outline-none transition-all shadow-sm">
                            </div>
                            @error('email')
                                <span class="text-rose-500 text-[10px] font-bold mt-1 block ml-1">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Password --}}
                        <div class="md:col-span-1">
                            <label
                                class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Contraseña</label>
                            <div class="relative mt-1">
                                <span class="absolute left-4 top-3.5 text-slate-400"><i
                                        class="fa-solid fa-lock text-sm"></i></span>
                                <input wire:model="password" type="password" placeholder="••••••••"
                                    class="w-full bg-white border border-slate-200 rounded-xl pl-11 pr-4 py-3 text-sm text-slate-700 focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 outline-none transition-all shadow-sm">
                            </div>
                            @error('password')
                                <span
                                    class="text-rose-500 text-[10px] font-bold mt-1 block ml-1">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Rol --}}
                        <div class="md:col-span-1">
                            <label
                                class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Rol</label>
                            <div class="relative mt-1">
                                <span class="absolute left-4 top-3.5 text-slate-400"><i
                                        class="fa-solid fa-user-shield text-sm"></i></span>
                                <select wire:model="role"
                                    class="w-full bg-white border border-slate-200 rounded-xl pl-11 pr-10 py-3 text-sm text-slate-700 focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 outline-none transition-all appearance-none shadow-sm">
                                    <option value="">Seleccionar...</option>
                                    @foreach ($roles as $rol)
                                        <option value="{{ $rol }}">{{ ucfirst($rol) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @error('role')
                                <span
                                    class="text-rose-500 text-[10px] font-bold mt-1 block ml-1">{{ $message }}</span>
                            @enderror
                        </div>

                    </div>

                    {{-- FOOTER (FIJO) --}}
                    <div class="px-8 py-6 bg-slate-50 flex justify-end gap-3 border-t flex-shrink-0">
                        <button type="button" wire:click="closeModal"
                            class="px-5 py-2.5 text-xs font-bold text-slate-500 hover:bg-slate-200 rounded-xl transition-colors">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="px-6 py-2.5 bg-orange-600 hover:bg-orange-700 text-white text-xs font-black rounded-xl shadow-lg shadow-orange-500/30 transition-all active:scale-95 uppercase tracking-widest">
                            <i class="fa-solid fa-floppy-disk mr-2"></i> Guardar Usuario
                        </button>
                    </div>
                </form>

            </div>
        </div>
    @endif
</div>
