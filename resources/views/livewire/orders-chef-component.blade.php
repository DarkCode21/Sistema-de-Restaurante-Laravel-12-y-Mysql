<div wire:poll.5s.keep-alive="refreshForAlerts" class="p-6 bg-[#fcfcfc] min-h-screen font-sans text-slate-900 antialiased">
    <div class="max-w-[1400px] mx-auto">

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-black tracking-tighter text-slate-800">MONITOR DE DESPACHO</h1>
                <p class="text-xs text-slate-400 font-bold uppercase tracking-widest">Control de platos por mesa</p>

                <div
                    class="flex items-center gap-2 mt-3 bg-slate-100 border border-slate-200/60 rounded-xl px-3 py-1.5 w-fit shadow-sm">
                    <span
                        class="bg-emerald-500 text-white w-5 h-5 rounded-md flex items-center justify-center shrink-0 shadow-sm">
                        <i class="fa-solid fa-check text-[10px]"></i>
                    </span>
                    <span class="text-[11px] font-bold text-slate-600 uppercase tracking-tight">
                        Presiona el check o alista todo el pedido
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <input wire:model.live="search" type="text" placeholder="Buscar mesa..."
                    class="min-w-0 flex-1 bg-white border border-slate-200 py-2 px-4 rounded-xl text-sm outline-none focus:ring-2 focus:ring-orange-500/20 transition-all md:w-64 shadow-sm">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-2 2xl:grid-cols-3 gap-6">
            @foreach ($corrections as $correction)
                <div wire:key="correction-{{ $correction->id }}"
                    class="border-2 border-rose-300 bg-rose-50 rounded-3xl p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <span class="inline-flex rounded-full bg-rose-600 px-2 py-1 text-[9px] font-black uppercase tracking-widest text-white">
                                {{ $correction->requires_kitchen ? 'Cocina' : 'Bar' }} ·
                                {{ $correction->action === 'cancel' ? 'Anular' : 'Actualizar' }}
                            </span>
                            <p class="mt-3 text-lg font-black uppercase text-slate-800">{{ $correction->product_name }}</p>
                            @if (!empty($correction->selected_options))
                                <p class="mt-1 text-xs font-bold text-orange-600">
                                    {{ collect($correction->selected_options)->map(fn ($option) => $option['group'] . ': ' . $option['value'])->join(' · ') }}
                                </p>
                            @endif
                            <p class="text-xs font-bold text-slate-500">
                                {{ $correction->table_name ?? $correction->order?->service_label ?? 'Pedido' }} ·
                                {{ $correction->action === 'cancel' ? 'Anular ' . $correction->quantity : 'Actualizar a ' . $correction->quantity }}
                            </p>
                            <p class="mt-2 text-xs font-bold text-rose-700">
                                NOTA: {{ $correction->notes ?: ($correction->action === 'cancel' ? 'SIN CAMBIOS' : 'SIN NOTA') }}
                            </p>
                        </div>
                        <div class="shrink-0">
                            <button wire:click="acknowledgeCorrection({{ $correction->id }})"
                                class="w-full rounded-xl bg-rose-600 px-3 py-2 text-[10px] font-black uppercase tracking-wide text-white transition hover:bg-rose-700 active:scale-95">
                                Confirmar
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach

            @forelse ($orders as $order)
                <div wire:key="order-{{ $order->id }}"
                    class="bg-white border border-slate-200 rounded-3xl flex flex-col shadow-sm hover:shadow-md transition-all overflow-hidden relative">

                    <div class="p-4 border-b border-slate-50 bg-slate-50/50 flex justify-between items-center">
                        <div>
                            <span class="text-[10px] font-black text-orange-600 uppercase tracking-tighter">{{ $order->order_type_label }}</span>
                            <h2 class="text-2xl font-black leading-none text-slate-800">{{ $order->service_label }}</h2>
                        </div>
                        <div class="text-right">
                            <span
                                class="text-[10px] font-bold text-slate-400 block">{{ $order->created_at->format('H:i A') }}</span>
                            <span
                                class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase bg-orange-100 text-orange-700">
                                {{ $order->status }}
                            </span>
                        </div>
                    </div>

                    <div class="p-4 flex-1 space-y-5 max-h-[400px] overflow-y-auto custom-scrollbar">
                        @foreach ($order->details as $detail)
                            <div
                                class="group relative bg-white p-3 pt-5 rounded-2xl border border-slate-100 shadow-sm transition-all">

                                <div class="absolute -top-2.5 left-3">
                                    @if ($detail->cooking_status === 'in_progress' || $detail->cooking_status === 'pending')
                                        <span
                                            class="bg-amber-500 text-white text-[8px] font-black px-2 py-0.5 rounded-md uppercase tracking-widest shadow-sm animate-pulse">Pendiente</span>
                                    @elseif($detail->cooking_status === 'ready')
                                        <span
                                            class="bg-emerald-500 text-white text-[8px] font-black px-2 py-0.5 rounded-md uppercase tracking-widest shadow-sm">Listo</span>
                                    @else
                                        <span
                                            class="bg-slate-200 text-slate-500 text-[8px] font-black px-2 py-0.5 rounded-md uppercase tracking-widest">Entregado</span>
                                    @endif
                                </div>

                                <div class="flex items-center justify-between gap-3">
                                    <div class="flex items-start gap-3 min-w-0 flex-1">
                                        <span
                                            class="bg-slate-100 text-slate-700 text-[10px] font-black w-6 h-6 rounded-lg flex items-center justify-center shrink-0">
                                            {{ $detail->quantity }}
                                        </span>
                                        <div class="min-w-0 flex-1">
                                             <p
                                                 class="text-sm font-black text-slate-800 uppercase leading-snug break-words">
                                                 {{ $detail->product->name }}
                                             </p>
                                            @if (!empty($detail->selected_options))
                                                <p class="mt-1 text-[10px] font-black text-orange-600">
                                                    {{ collect($detail->selected_options)->map(fn ($option) => $option['group'] . ': ' . $option['value'])->join(' · ') }}
                                                </p>
                                            @endif
                                            @if ($detail->preparationStation)
                                                <p class="mt-1 text-[9px] font-black uppercase tracking-wide text-slate-400">{{ $detail->preparationStation->name }}</p>
                                            @endif
                                            @if ($detail->notes)
                                                <p
                                                    class="text-[10px] text-orange-500 font-bold italic mt-1 leading-tight uppercase bg-orange-50/50 p-1 rounded-lg">
                                                    "{{ $detail->notes }}"
                                                </p>
                                            @endif
                                        </div>
                                    </div>

                                    @if ($detail->cooking_status === 'in_progress' || $detail->cooking_status === 'pending')
                                        <div class="shrink-0">
                                            <button wire:click="markDetailAsReady({{ $detail->id }})"
                                                title="Marcar como listo"
                                                class="bg-slate-100 hover:bg-emerald-500 text-slate-700 hover:text-white w-8 h-8 rounded-xl flex items-center justify-center transition-all active:scale-90 border border-slate-200/60 shadow-sm">
                                                <i class="fa-solid fa-check text-xs"></i>
                                            </button>
                                        </div>
                                    @endif
                                </div>

                            </div>
                        @endforeach
                    </div>

                    <div class="p-4 bg-slate-50/80 border-t border-slate-50 mt-auto">
                        <div class="flex justify-between items-center">
                            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter">
                                ID: #{{ $order->id }} | {{ $order->user->name ?? 'Sistema' }}
                            </span>
                        </div>
                    </div>

                    <div class="p-3 pt-0">
                        <button wire:click="markOrderAsReady({{ $order->id }})" type="button"
                            class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs py-2 px-4 rounded-xl flex items-center justify-center gap-2 transition-all active:scale-[0.98] shadow-sm">
                            <i class="fa-solid fa-check-double text-[11px]"></i>
                            Alistar todo
                        </button>
                    </div>
                </div>
            @empty
                @if ($corrections->isEmpty())
                    <div
                        class="col-span-1 md:col-span-2 xl:col-span-2 2xl:col-span-3 bg-white border border-dashed border-slate-300 rounded-3xl p-12 text-center">
                        <div class="flex flex-col items-center justify-center gap-3 max-w-sm mx-auto">
                            <div
                                class="h-14 w-14 rounded-2xl bg-slate-50 text-slate-400 flex items-center justify-center text-2xl shadow-sm">
                                <i class="fa-solid fa-fire-burner"></i>
                            </div>
                            <h3 class="text-base font-black text-slate-800 uppercase tracking-tight">No hay pedidos en
                                cocina</h3>
                            <p class="text-xs text-slate-400 leading-normal">El monitor de preparación está vacío. Las
                                comandas enviadas por los meseros que requieran elaboración aparecerán en esta pantalla en
                                tiempo real.</p>
                        </div>
                    </div>
                @endif
            @endforelse
        </div>

        <div class="mt-8">
            {{ $orders->links() }}
        </div>
    </div>

    <div id="kitchen-alert-toast" class="fixed right-4 top-4 z-[120] hidden max-w-sm rounded-2xl border border-orange-200 bg-white p-4 shadow-xl" role="status" aria-live="polite">
        <div class="flex items-start gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-orange-100 text-orange-600">
                <i class="fa-solid fa-bell"></i>
            </span>
            <div>
                <p class="text-sm font-black text-slate-800">Nueva comanda en cocina</p>
                <p class="text-xs font-semibold text-slate-500" data-kitchen-alert-message></p>
            </div>
        </div>
    </div>
</div>

<script>
    const registerKitchenBellListeners = () => {
        if (window.__kitchenBellListenersRegistered) {
            return;
        }

        window.__kitchenBellListenersRegistered = true;
        let audioContext;
        const bellEnabled = @js((bool) ($empresa->alert_sounds_enabled ?? true));
        let toastTimeout;

        const activateAudio = async () => {
            const AudioContext = window.AudioContext || window.webkitAudioContext;

            if (!AudioContext) {
                return;
            }

            audioContext ??= new AudioContext();
            await audioContext.resume();
        };

        const ringBell = () => {
            if (!bellEnabled || !audioContext || audioContext.state !== 'running') {
                return;
            }

            [880, 1175].forEach((frequency, index) => {
                const oscillator = audioContext.createOscillator();
                const gain = audioContext.createGain();
                const startAt = audioContext.currentTime + (index * 0.14);

                oscillator.type = 'sine';
                oscillator.frequency.value = frequency;
                gain.gain.setValueAtTime(0.0001, startAt);
                gain.gain.exponentialRampToValueAtTime(0.18, startAt + 0.02);
                gain.gain.exponentialRampToValueAtTime(0.0001, startAt + 0.55);
                oscillator.connect(gain).connect(audioContext.destination);
                oscillator.start(startAt);
                oscillator.stop(startAt + 0.56);
            });
        };

        document.addEventListener('pointerdown', () => {
            if (bellEnabled) {
                activateAudio().catch(() => {});
            }
        }, { once: true });

        Livewire.on('kitchen-order-received', (event) => {
            const toast = document.querySelector('#kitchen-alert-toast');
            const message = document.querySelector('[data-kitchen-alert-message]');
            const count = event.orderIds?.length ?? 1;

            if (!toast || !message) {
                return;
            }

            message.textContent = count === 1
                ? 'Hay una nueva orden esperando preparación.'
                : `Hay ${count} nuevas órdenes esperando preparación.`;
            toast.classList.remove('hidden');
            ringBell();
            clearTimeout(toastTimeout);
            toastTimeout = setTimeout(() => toast.classList.add('hidden'), 5000);
        });

        Livewire.on('kitchen-correction-received', () => {
            const toast = document.querySelector('#kitchen-alert-toast');
            const message = document.querySelector('[data-kitchen-alert-message]');

            if (!toast || !message) {
                return;
            }

            message.textContent = 'Hay una corrección pendiente de revisar.';
            toast.classList.remove('hidden');
            ringBell();
            clearTimeout(toastTimeout);
            toastTimeout = setTimeout(() => toast.classList.add('hidden'), 5000);
        });
    };

    if (window.Livewire) {
        registerKitchenBellListeners();
    } else {
        document.addEventListener('livewire:init', registerKitchenBellListeners, { once: true });
    }
</script>
