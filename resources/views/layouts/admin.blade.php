<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>

    @if (isset($empresa) && $empresa->favicon_path)
        <link rel="icon" type="image/x-icon" href="{{ asset('storage/' . $empresa->favicon_path) }}">
        <link rel="shortcut icon" href="{{ asset('storage/' . $empresa->favicon_path) }}" type="image/x-icon">
    @else
        <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    @endif

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles

    <style>
        /* Clase para aplicar al sidebar */
        .no-scrollbar {
            /* Firefox */
            scrollbar-width: none;

            /* IE y Edge */
            -ms-overflow-style: none;
        }

        /* Chrome, Safari y Opera */
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }

        /* Estilo opcional para que el scroll se vea más limpio */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #e2e8f0;
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #cbd5e1;
        }

        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body x-data="sidebarLayout()" class="font-sans antialiased bg-[#f1f5f9] text-slate-900">

    <div class="flex h-screen overflow-hidden">

        @include('layouts.sidebar')

        <div class="relative flex flex-col flex-1 overflow-y-auto overflow-x-hidden">
            @include('layouts.navigation')

            <main class="flex-grow">
                <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        document.addEventListener('livewire:init', () => {

            Alpine.data('sidebarLayout', () => ({
                sidebarOpen: false,
                sidebarExpanded: true,

                toggleSidebar() {
                    this.sidebarExpanded = !this.sidebarExpanded;
                },

                openMobileSidebar() {
                    this.sidebarOpen = true;
                }
            }));
            Livewire.on('swal', (event) => {
                const data = event[0];
                Swal.fire({
                    title: data.title,
                    text: data.text,
                    icon: data.icon,
                    toast: true,
                    timer: 3000,
                    position: 'top-end',
                    showConfirmButton: false,
                    customClass: {
                        confirmButton: 'rounded-xl px-6 py-2.5 text-xs font-bold uppercase tracking-widest'
                    }
                });
            });

            // Confirmación de eliminación
            Livewire.on('confirm-delete', (valor) => {
                Swal.fire({
                    title: '¿Estás seguro?',
                    text: "Esta acción eliminará al usuario permanentemente.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#374151',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar',
                    customClass: {
                        popup: 'rounded-[2rem]',
                        confirmButton: 'rounded-xl px-6 py-2.5 text-xs font-bold uppercase tracking-widest',
                        cancelButton: 'rounded-xl px-6 py-2.5 text-xs font-bold uppercase tracking-widest'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        Livewire.dispatch('delete-confirmed', {
                            id: valor.id
                        });
                    }
                });
            });
        });

        function impresionDirecta(url, impresora_name) {
            fetch("{{ env('IMPRESION_LOCAL_URL') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        venta_url: url,
                        printer: impresora_name
                    })
                })
                .then(r => r.json())
                .then(res => {
                    Swal.close();

                    if (!res.success) {
                        Swal.fire({
                            title: "Error de impresión",
                            text: res.message ||
                                "Ocurrió un error al intentar imprimir. Por favor, verifica la configuración de tu impresora.",
                            icon: "error",
                            toast: true,
                            timer: 3000,
                            position: 'top-end',
                            showConfirmButton: false,
                            customClass: {
                                confirmButton: 'rounded-xl px-6 py-2.5 text-xs font-bold uppercase tracking-widest'
                            }
                        });

                        return;
                    }
                })
                .catch(err => {
                    Swal.close();
                    Swal.fire({
                        title: "Error de impresión",
                        text: "No se pudo conectar con el servicio de impresión local. Por favor, verifica que esté en ejecución.",
                        icon: "error",
                        toast: true,
                        timer: 3000,
                        position: 'top-end',
                        showConfirmButton: false,
                        customClass: {
                            confirmButton: 'rounded-xl px-6 py-2.5 text-xs font-bold uppercase tracking-widest'
                        }
                    });
                });
        }

        function abrirVentanaEmergente(url) {
            const width = 400;
            const height = 600;
            const left = (screen.width - width) / 2;
            const top = (screen.height - height) / 2;

            window.open(url, '_blank', `width=${width},height=${height},left=${left},top=${top}`);
        }

        function abrirModalImpresion(kitchenUrl = '', barUrl = '') {

            const modal = document.getElementById('printModal');

            @if ($empresa->separate_orders)

                const kitchenFrame = document.getElementById('printFrameKitchen');
                const barFrame = document.getElementById('printFrameBar');

                if (kitchenFrame) {
                    kitchenFrame.src = kitchenUrl || '';
                }

                if (barFrame) {
                    barFrame.src = barUrl || '';
                }
            @else

                const printFrame = document.getElementById('printFrame');

                if (printFrame) {
                    printFrame.src = kitchenUrl || '';
                }
            @endif

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function cerrarModalImpresion() {

            const modal = document.getElementById('printModal');

            @if ($empresa->separate_orders)

                const kitchenFrame = document.getElementById('printFrameKitchen');
                const barFrame = document.getElementById('printFrameBar');

                if (kitchenFrame) {
                    kitchenFrame.src = '';
                }

                if (barFrame) {
                    barFrame.src = '';
                }
            @else

                const printFrame = document.getElementById('printFrame');

                if (printFrame) {
                    printFrame.src = '';
                }
            @endif

            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    </script>

    @stack('js')

    @livewireScripts

</body>

</html>
