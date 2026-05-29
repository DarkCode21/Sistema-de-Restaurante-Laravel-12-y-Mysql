<div class="p-4 bg-[#fcfcfc] min-h-screen font-sans text-slate-900 antialiased">
    <div class="max-w-[1400px] mx-auto flex flex-col lg:flex-row gap-6">

        <div class="flex-1 min-w-0">
            <div class="flex gap-2 mb-6">
                <div class="relative flex-1">
                    <i
                        class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                    <input wire:model.live="search" type="text" placeholder="Buscar platillo..."
                        class="w-full bg-white border border-slate-200 py-2 pl-10 pr-4 rounded-lg text-sm outline-none focus:border-orange-500 transition-all">
                </div>

                <a type="button" href="{{ route('tables.index') }}"
                    class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg text-sm font-medium transition-colors flex items-center gap-2">
                    <i class="fa-solid fa-xmark"></i>
                    Cancelar
                </a>
            </div>

            <div class="flex flex-wrap gap-2 mb-8">
                <button wire:click="$set('category_id', '')"
                    class="px-4 py-1.5 rounded-md text-[11px] font-bold tracking-tight transition-all border {{ $category_id == '' ? 'bg-orange-600 border-orange-600 text-white shadow-sm' : 'bg-white border-slate-200 text-slate-500 hover:border-slate-300' }}">
                    TODOS
                </button>
                @foreach ($categories as $category)
                    <button wire:click="$set('category_id', {{ $category->id }})"
                        class="px-4 py-1.5 rounded-md text-[11px] font-bold tracking-tight transition-all border {{ $category_id == $category->id ? 'bg-orange-600 border-orange-600 text-white shadow-sm' : 'bg-white border-slate-200 text-slate-500 hover:border-slate-300' }}">
                        {{ strtoupper($category->name) }}
                    </button>
                @endforeach
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-4">
                @foreach ($products as $product)
                    <div
                        class="group bg-white border border-slate-100 rounded-xl overflow-hidden hover:shadow-md transition-all">
                        <div class="relative aspect-[16/10] overflow-hidden bg-slate-50">
                            <img src="{{ asset('storage/' . $product->image) }}"
                                class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
                            <div
                                class="absolute top-2 left-2 bg-white/90 backdrop-blur-sm px-2 py-0.5 rounded text-[9px] font-black text-orange-600 uppercase">
                                {{ $product->category->name }}
                            </div>
                        </div>

                        <div class="p-3">
                            <h3 class="font-bold text-slate-800 text-sm leading-snug mb-3 line-clamp-1">
                                {{ $product->name }}
                            </h3>
                            <div class="flex items-center justify-between">
                                <span class="text-base font-black text-slate-900">
                                    {{ $empresa->currency_simbol }}{{ number_format($product->price, 2) }}
                                </span>
                                <button wire:click="addToOrder({{ $product->id }})"
                                    class="bg-orange-600 text-white w-8 h-8 rounded-lg flex items-center justify-center hover:bg-orange-700 active:scale-90 transition-all">
                                    +
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-3">
                {{ $products->links() }}
            </div>
        </div>

        <div class="w-full lg:w-[400px] shrink-0">
            <div
                class="bg-white border border-slate-200 rounded-xl flex flex-col h-[calc(100vh-40px)] sticky top-5 shadow-sm">

                <div class="p-5 border-b border-slate-50">
                    <div class="flex justify-between items-center">
                        <h2 class="text-base font-black uppercase tracking-tighter text-slate-800">Pedido Actual</h2>
                        <span class="text-[9px] bg-slate-100 text-slate-500 px-2 py-0.5 rounded font-bold">
                            MESA {{ $table->name }}
                        </span>
                    </div>
                </div>

                <div class="px-5 py-3 bg-slate-50/50 border-b border-slate-100">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                        Cliente
                    </label>
                    <div class="flex items-stretch gap-1">
                        <div class="relative flex-1">
                            <i
                                class="fas fa-user text-[10px] absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                            <input type="text" readonly placeholder="Consumidor Final"
                                value="{{ $customer_name ?? '' }}"
                                class="w-full h-9 pl-8 pr-2 bg-white border border-slate-200 rounded-l-lg text-xs font-semibold text-slate-700 focus:outline-none cursor-default border-r-0">
                        </div>
                        <button wire:click="openCustomerModal" type="button"
                            class="flex items-center justify-center w-9 h-9 bg-orange-50 text-orange-600 rounded-r-lg border border-slate-200 hover:bg-orange-600 hover:text-white transition-all active:scale-95 shadow-sm">
                            <i class="fas fa-user-plus text-xs"></i>
                        </button>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto p-4 custom-scrollbar">
                    @forelse($cart as $productId => $item)
                        <div wire:key="cart-item-{{ $productId }}"
                            class="group relative mb-6 bg-white p-3 pt-5 rounded-2xl border border-slate-100 hover:border-orange-100 transition-all shadow-sm">

                            <div class="absolute -top-2.5 left-4 z-10">
                                @if (!$item['detail_id'])
                                    <span
                                        class="bg-blue-100 text-blue-700 text-[9px] font-black px-2 py-0.5 rounded-full border border-blue-200 uppercase tracking-tighter shadow-sm">No
                                        Guardado
                                    </span>
                                @elseif($item['cooking_status'] === 'pending')
                                    <span
                                        class="bg-amber-100 text-amber-700 text-[9px] font-black px-2 py-0.5 rounded-full border border-amber-200 uppercase tracking-tighter animate-pulse shadow-sm">Pendiente</span>
                                @elseif($item['cooking_status'] === 'in_progress')
                                    <span
                                        class="bg-cyan-100 text-cyan-700 text-[9px] font-black px-2 py-0.5 rounded-full border border-cyan-200 uppercase tracking-tighter animate-pulse shadow-sm">En
                                        Proceso
                                    </span>
                                @elseif($item['cooking_status'] === 'ready')
                                    <span
                                        class="bg-emerald-500 text-white text-[9px] font-black px-2 py-0.5 rounded-full border border-emerald-600 uppercase tracking-tighter shadow-md">¡Listo!</span>
                                @elseif($item['cooking_status'] === 'served')
                                    <span
                                        class="bg-slate-100 text-slate-500 text-[9px] font-black px-2 py-0.5 rounded-full border border-slate-200 uppercase tracking-tighter shadow-sm">Servido</span>
                                @endif
                            </div>

                            <div
                                class="absolute -top-2.5 right-4 z-10 flex items-center shadow-sm rounded-full overflow-hidden border border-slate-200 bg-white">
                                @if ($item['detail_id'] && $item['cooking_status'] == 'ready')
                                    <button wire:click="markAsServed({{ $item['detail_id'] }})"
                                        title="Marcar como entregado"
                                        class="bg-white hover:bg-emerald-600 text-emerald-600 hover:text-white text-[9px] font-black px-3 py-1 transition-all active:scale-95 uppercase tracking-tighter border-r border-slate-100 flex items-center gap-1">
                                        <i class="fas fa-check-double"></i>
                                        <span>Entregar</span>
                                    </button>
                                @endif

                                <button wire:click="removeItem({{ $productId }})" title="Eliminar ítem"
                                    class="bg-white hover:bg-rose-500 text-slate-400 hover:text-white text-[9px] px-2.5 py-1 transition-all active:scale-95">
                                    <i class="fas fa-trash-can"></i>
                                </button>
                            </div>

                            <div class="flex items-start gap-4">
                                <div
                                    class="flex flex-col items-center bg-slate-50 rounded-lg p-1 border border-slate-100">
                                    <button wire:click="increment({{ $productId }})"
                                        class="w-7 h-7 flex items-center justify-center bg-white border border-slate-200 rounded-md text-slate-600 hover:bg-orange-600 hover:text-white transition-colors shadow-sm active:scale-90">
                                        <i class="fas fa-plus text-[10px]"></i>
                                    </button>

                                    <span class="py-1 text-xs font-black text-slate-700">
                                        {{ $item['quantity'] }}
                                    </span>

                                    <button wire:click="decrement({{ $productId }})"
                                        class="w-7 h-7 flex items-center justify-center bg-white border border-slate-200 rounded-md text-slate-600 hover:bg-rose-500 hover:text-white transition-colors shadow-sm active:scale-90">
                                        <i class="fas fa-minus text-[10px]"></i>
                                    </button>
                                </div>

                                <div class="flex-1 min-w-0">
                                    <div class="flex justify-between items-start mb-1">
                                        <div class="flex-1">
                                            <h4
                                                class="text-sm font-black text-slate-800 truncate tracking-tight uppercase">
                                                {{ $item['name'] }}
                                            </h4>
                                        </div>
                                        <div class="text-right ml-2">
                                            <p class="text-sm font-black text-slate-900 tracking-tighter">
                                                {{ $empresa->currency_simbol }}{{ number_format($item['subtotal'], 2) }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="relative mt-2">
                                        <i class="fas fa-pen text-[8px] absolute left-2 top-2.5 text-slate-300"></i>
                                        <input type="text" value="{{ $item['notes'] ?? '' }}"
                                            @if ($item['detail_id']) wire:change="updateNote({{ $item['detail_id'] }}, $event.target.value)"
                                            @else
                                                wire:change="$set('cart.{{ $productId }}.notes', $event.target.value)" @endif
                                            placeholder="Añadir nota a la cocina..."
                                            class="w-full pl-6 pr-2 py-1.5 bg-slate-50 border-none rounded-lg text-[10px] focus:ring-1 focus:ring-orange-500 outline-none italic text-slate-500 placeholder:text-slate-300 transition-all">
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div
                            class="flex flex-col items-center justify-center py-12 px-4 border-2 border-dashed border-slate-100 rounded-3xl bg-slate-50/50">
                            <i class="fas fa-utensils text-slate-200 text-2xl mb-4"></i>
                            <h3 class="text-slate-500 font-black text-sm uppercase tracking-widest text-center">Sin
                                pedidos</h3>
                        </div>
                    @endforelse
                </div>

                <div class="p-5 border-t bg-slate-50/30">
                    <div class="flex justify-between mb-4">
                        <span class="text-sm font-bold text-slate-500 uppercase tracking-tight">Monto en Carrito</span>
                        <span class="font-black text-xl text-slate-900">
                            {{ $empresa->currency_simbol }}{{ number_format($cartTotal, 2) }}
                        </span>
                    </div>

                    <div class="flex flex-col gap-2">
                        @if (count($cart) > 0)
                            <button wire:click="saveOrderTransaction"
                                class="w-full px-5 py-2.5 bg-blue-600 text-white text-xs font-black uppercase tracking-widest rounded-lg hover:bg-blue-700 shadow-md shadow-blue-100 active:scale-95 transition-all flex items-center justify-center gap-2">
                                <i class="fas fa-paper-plane text-sm"></i>
                                Confirmar y Enviar Pedido
                            </button>
                        @endif


                    </div>


                </div>

            </div>
        </div>
    </div>

    @if ($isOpenCustomerModal)
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center z-[100] p-4">
            <div x-data="{ tab: 'list' }"
                class="bg-white rounded-[2rem] w-full max-w-lg shadow-2xl overflow-hidden transition-all">

                <div class="px-8 py-5 border-b border-slate-100 bg-slate-50/50">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-black text-slate-800 uppercase tracking-tighter">
                            <span x-show="tab === 'list'">Seleccionar Cliente</span>
                            <span x-show="tab === 'create'">Nuevo Cliente</span>
                        </h3>
                        <button wire:click="closeCustomerModal" class="text-slate-400 hover:text-slate-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div class="flex bg-slate-200/50 p-1 rounded-xl">
                        <button @click="tab = 'list'"
                            :class="tab === 'list' ? 'bg-white shadow-sm text-orange-600' :
                                'text-slate-500 hover:text-slate-700'"
                            class="flex-1 py-2 text-[10px] font-black uppercase tracking-widest rounded-lg transition-all">
                            <i class="fas fa-list mr-2"></i> Listado
                        </button>
                        <button @click="tab = 'create'"
                            :class="tab === 'create' ? 'bg-white shadow-sm text-orange-600' :
                                'text-slate-500 hover:text-slate-700'"
                            class="flex-1 py-2 text-[10px] font-black uppercase tracking-widest rounded-lg transition-all">
                            <i class="fas fa-plus mr-2"></i> Crear Nuevo
                        </button>
                    </div>
                </div>

                <div x-show="tab === 'list'" class="p-0">
                    <div class="p-6 pb-2">
                        <div class="relative">
                            <i
                                class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            <input wire:model.live="searchCustomer" type="text"
                                placeholder="Buscar por nombre, DNI o RUC..."
                                class="w-full pl-10 pr-4 py-3 bg-slate-100 border-none rounded-2xl text-sm focus:ring-2 focus:ring-orange-500 transition-all">
                        </div>
                    </div>

                    <div class="max-h-[350px] overflow-y-auto px-6 custom-scrollbar">
                        <div class="space-y-2 pb-6">
                            @forelse($customers as $customer)
                                <button wire:click="selectCustomer({{ $customer->id }})"
                                    class="w-full flex items-center justify-between p-4 rounded-2xl border border-slate-100 hover:border-orange-200 hover:bg-orange-50/50 transition-all group text-left">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 group-hover:bg-orange-100 group-hover:text-orange-600 transition-colors">
                                            <i class="fas fa-user text-sm"></i>
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-bold text-slate-700">{{ $customer->name }}</h4>
                                            <p class="text-[10px] text-slate-400 font-medium">
                                                {{ $customer->document_number ?? 'Sin documento' }}</p>
                                        </div>
                                    </div>
                                    <i
                                        class="fas fa-chevron-right text-slate-300 group-hover:text-orange-400 text-xs"></i>
                                </button>
                            @empty
                                <div class="py-10 text-center">
                                    <i class="fas fa-users text-slate-200 text-3xl mb-3"></i>
                                    <p class="text-slate-500 text-xs font-medium">No se encontraron clientes</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="px-8 py-4 bg-slate-50 border-t border-slate-100">
                        {{ $customers->links(data: ['scrollTo' => false]) }}
                    </div>
                </div>

                <div x-show="tab === 'create'" x-cloak>
                    <form wire:submit.prevent="saveCustomer">
                        <div class="px-8 py-8 space-y-5">
                            <div class="grid grid-cols-1 gap-5">
                                <div>
                                    <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Nombre Completo
                                        / Razón Social</label>
                                    <input wire:model="newCustomer.name" type="text"
                                        class="w-full mt-1 bg-slate-50 border-slate-200 rounded-xl px-4 py-3 text-sm focus:border-orange-500 focus:ring-orange-500">
                                    @error('newCustomer.name')
                                        <span
                                            class="text-rose-500 text-[10px] font-bold mt-1 ml-1">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Documento
                                            (DNI/RUC)</label>
                                        <input wire:model="newCustomer.document_number" type="text"
                                            class="w-full mt-1 bg-slate-50 border-slate-200 rounded-xl px-4 py-3 text-sm">
                                    </div>
                                    <div>
                                        <label
                                            class="text-[10px] font-black uppercase text-slate-400 ml-1">Teléfono</label>
                                        <input wire:model="newCustomer.phone" type="text"
                                            class="w-full mt-1 bg-slate-50 border-slate-200 rounded-xl px-4 py-3 text-sm">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="px-8 py-6 bg-slate-50 border-t flex justify-end gap-3">
                            <button type="button" @click="tab = 'list'"
                                class="px-6 py-2.5 text-[10px] font-black uppercase tracking-widest text-slate-500 hover:text-slate-700 transition-all">
                                Volver al listado
                            </button>
                            <button type="submit"
                                class="px-8 py-2.5 bg-orange-600 text-white text-[10px] font-black uppercase tracking-widest rounded-xl shadow-md shadow-orange-100 hover:bg-orange-700 active:scale-95 transition-all">
                                Guardar y Seleccionar
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    @endif

    <!-- Modal -->
    <div id="printModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4 sm:p-6">

        <div class="bg-white w-full max-w-7xl rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">

            <div class="flex items-center justify-between border-b px-6 py-4 shrink-0">
                <h2 class="text-lg font-semibold text-gray-800">
                    Vista previa de impresión
                </h2>
                <button onclick="cerrarModalImpresion()"
                    class="text-gray-500 hover:text-red-500 text-2xl leading-none transition-colors">
                    &times;
                </button>
            </div>

            <div class="overflow-y-auto p-4 bg-gray-100 flex-1">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

                    @if ($separate_orders)
                        <div class="bg-white rounded-xl shadow overflow-hidden flex flex-col">
                            <div class="px-4 py-2 border-b font-semibold text-gray-700 bg-gray-50">
                                Cocina
                            </div>
                            <iframe id="printFrameKitchen" src=""
                                class="w-full h-[40vh] lg:h-[60vh] border-0">
                            </iframe>
                        </div>

                        <div class="bg-white rounded-xl shadow overflow-hidden flex flex-col">
                            <div class="px-4 py-2 border-b font-semibold text-gray-700 bg-gray-50">
                                Bar
                            </div>
                            <iframe id="printFrameBar" src="" class="w-full h-[40vh] lg:h-[60vh] border-0">
                            </iframe>
                        </div>
                    @else
                        <div class="bg-white rounded-xl shadow overflow-hidden flex flex-col lg:col-span-2">
                            <div class="px-4 py-2 border-b font-semibold text-gray-700 bg-gray-50">
                                Todo
                            </div>
                            <iframe id="printFrame" src="" class="w-full h-[40vh] lg:h-[60vh] border-0">
                            </iframe>
                        </div>
                    @endif

                </div>
            </div>

            <div class="flex justify-end gap-3 border-t px-6 py-4 shrink-0 bg-white">
                <button onclick="cerrarModalImpresion()"
                    class="px-4 py-2 rounded-lg bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium transition">
                    Cerrar
                </button>
            </div>

        </div>
    </div>

</div>



<script>
    document.addEventListener('livewire:init', () => {

        Livewire.on('auto-print-kitchen', async (printers) => {

            let kitchenUrl = '';
            let barUrl = '';

            for (const printerData of printers[0]) {

                let finalUrl = printerData.url;

                // SOLO SI SEPARA PEDIDOS
                @if ($separate_orders)

                    finalUrl +=
                        '?requires_kitchen=' +
                        printerData.requires_kitchen;
                @endif

                // SIN impresión directa
                @if (!$direct_printing)

                    @if ($separate_orders)

                        if (printerData.requires_kitchen) {
                            kitchenUrl = finalUrl;
                        } else {
                            barUrl = finalUrl;
                        }
                    @else

                        kitchenUrl = finalUrl;
                    @endif
                @else

                    await printKitchenTicket(
                        finalUrl,
                        printerData.printer_name
                    );

                    await delay(2000);
                @endif
            }

            // ABRIR SOLO UNA VEZ
            @if (!$direct_printing)
                abrirModalImpresion(kitchenUrl, barUrl);
            @endif

        });

    });

    function delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    async function printKitchenTicket(url, printer_name) {

        try {

            const response = await fetch("{{ env('IMPRESION_LOCAL_URL') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    venta_url: url,
                    printer: printer_name
                })
            });

            const res = await response.json();

            if (!res.success) {

                Swal.fire({
                    title: "Error de impresión",
                    text: res.message || "Error al imprimir.",
                    icon: "error",
                    toast: true,
                    timer: 3000,
                    position: 'top-end',
                    showConfirmButton: false
                });

                return false;
            }

            return true;

        } catch (err) {

            Swal.fire({
                title: "Error de impresión",
                text: "No se pudo conectar con el servicio local.",
                icon: "error",
                toast: true,
                timer: 3000,
                position: 'top-end',
                showConfirmButton: false
            });

            return false;
        }
    }
</script>
