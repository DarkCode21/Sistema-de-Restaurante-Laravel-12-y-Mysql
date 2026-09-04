<div class="mx-auto max-w-5xl p-4 sm:p-6">
    <div class="mb-6">
        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-orange-600">Estructura del negocio</p>
        <h1 class="mt-1 text-xl font-black text-slate-900">Empresas y sedes</h1>
    </div>

    <div class="grid gap-5 lg:grid-cols-2">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-black text-slate-800">Nueva empresa</h2>
            <form wire:submit="createCompany" class="mt-4 grid gap-3">
                <input wire:model="company_name" placeholder="Nombre comercial" class="rounded-lg border-slate-200 text-sm focus:border-orange-500 focus:ring-orange-500">
                <div class="grid gap-3 sm:grid-cols-2">
                    <input wire:model="initial_branch_name" placeholder="Sede principal" class="rounded-lg border-slate-200 text-sm focus:border-orange-500 focus:ring-orange-500">
                    <input wire:model="initial_branch_code" placeholder="Código" class="rounded-lg border-slate-200 text-sm uppercase focus:border-orange-500 focus:ring-orange-500">
                </div>
                <button class="rounded-lg bg-slate-900 py-2.5 text-[10px] font-black uppercase text-white hover:bg-slate-700">Crear empresa</button>
            </form>
            @error('company_name') <p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p> @enderror
            @error('initial_branch_name') <p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p> @enderror
            @error('initial_branch_code') <p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p> @enderror
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-black text-slate-800">Nueva sede</h2>
            <form wire:submit="createBranch" class="mt-4 grid gap-3">
                <select wire:model="branch_company_id" class="rounded-lg border-slate-200 text-sm focus:border-orange-500 focus:ring-orange-500">
                    <option value="">Seleccionar empresa</option>
                    @foreach ($companies as $company)<option value="{{ $company->id }}">{{ $company->name }}</option>@endforeach
                </select>
                <div class="grid gap-3 sm:grid-cols-2">
                    <input wire:model="branch_name" placeholder="Nombre de sede" class="rounded-lg border-slate-200 text-sm focus:border-orange-500 focus:ring-orange-500">
                    <input wire:model="branch_code" placeholder="Código" class="rounded-lg border-slate-200 text-sm uppercase focus:border-orange-500 focus:ring-orange-500">
                </div>
                <input wire:model="branch_address" placeholder="Dirección (opcional)" class="rounded-lg border-slate-200 text-sm focus:border-orange-500 focus:ring-orange-500">
                <input wire:model="branch_phone" placeholder="Teléfono (opcional)" class="rounded-lg border-slate-200 text-sm focus:border-orange-500 focus:ring-orange-500">
                <button class="rounded-lg bg-orange-600 py-2.5 text-[10px] font-black uppercase text-white hover:bg-orange-700">Agregar sede</button>
            </form>
            @error('branch_company_id') <p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p> @enderror
            @error('branch_name') <p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p> @enderror
            @error('branch_code') <p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p> @enderror
        </section>
    </div>

    <section class="mt-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-sm font-black text-slate-800">Mis empresas</h2>
        <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($companies as $company)
                <article class="rounded-xl bg-slate-50 p-4">
                    <p class="font-black text-slate-700">{{ $company->name }}</p>
                    <p class="mt-2 text-xs font-bold text-slate-400">{{ $company->branches->pluck('name')->join(' · ') ?: 'Sin sedes asignadas' }}</p>
                </article>
            @empty
                <p class="text-sm text-slate-500">No tienes empresas asignadas.</p>
            @endforelse
        </div>
    </section>
</div>
