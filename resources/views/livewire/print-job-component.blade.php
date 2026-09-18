<div class="mx-auto max-w-6xl space-y-6">
    <div>
        <h1 class="text-2xl font-extrabold text-slate-800">Cola de impresión</h1>
        <p class="mt-1 text-sm text-slate-500">Reintenta trabajos fallidos o reimprime tickets confirmados de esta sede.</p>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-400">
                <tr>
                    <th class="px-5 py-4">Pedido</th>
                    <th class="px-5 py-4">Destino</th>
                    <th class="px-5 py-4">Estado</th>
                    <th class="px-5 py-4">Intentos</th>
                    <th class="px-5 py-4 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($jobs as $job)
                    <tr>
                        <td class="px-5 py-4 font-semibold text-slate-700">#{{ $job->order_id }} {{ $job->is_correction ? 'Corrección' : '' }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ $job->preparationStation?->name ?? 'General' }} · {{ $job->printer_name }}</td>
                        <td class="px-5 py-4">
                            <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $job->status === 'sent' ? 'bg-emerald-100 text-emerald-700' : ($job->status === 'failed' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700') }}">
                                {{ $job->status === 'sent' ? 'Confirmado' : ($job->status === 'failed' ? 'Fallido' : 'Pendiente') }}
                            </span>
                            @if ($job->error)<p class="mt-1 text-xs text-rose-600">{{ $job->error }}</p>@endif
                        </td>
                        <td class="px-5 py-4 text-slate-600">{{ $job->attempts }}</td>
                        <td class="px-5 py-4 text-right">
                            @if ($job->status !== 'sent')
                                <button wire:click="retry({{ $job->id }})" class="rounded-lg bg-orange-600 px-3 py-2 text-xs font-bold text-white">Reintentar</button>
                            @endif
                            <button wire:click="reprint({{ $job->id }})" class="ml-2 rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600">Reimprimir</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-12 text-center text-slate-400">No hay trabajos de impresión para esta sede.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t border-slate-100 px-5 py-4">{{ $jobs->links() }}</div>
    </div>
</div>
