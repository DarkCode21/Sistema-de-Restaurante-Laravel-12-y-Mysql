<div class="fixed inset-0 bg-slate-900 bg-opacity-30 z-40 lg:hidden transition-opacity duration-200" x-show="sidebarOpen"
    @click="sidebarOpen = false" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-100"
    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" x-cloak></div>

<div id="sidebar"
    class="flex flex-col absolute z-40 left-0 top-0 lg:static lg:left-auto lg:top-auto lg:translate-x-0 h-screen overflow-y-auto no-scrollbar bg-white border-r border-slate-200 transition-all duration-300 ease-in-out shadow-sm"
    :class="[sidebarExpanded ? 'w-64' : 'w-20', sidebarOpen ? 'translate-x-0' : '-translate-x-64']" x-cloak>

    <div class="flex items-center justify-between h-20 px-5 bg-slate-50/50 border-b border-slate-100 shrink-0">
        <a class="flex items-center gap-3 overflow-hidden" href="{{ route('dashboard') }}">
            <x-application-logo class="w-10 h-10 fill-current text-orange-600 shrink-0" />
            <span class="font-bold text-xl transition-all duration-300 truncate"
                :class="sidebarExpanded ? 'opacity-100' : 'opacity-0 invisible w-0'">
                {{ $empresa->company_name ?? config('app.name', 'Finanzas') }}
            </span>
        </a>
    </div>

    <nav class="flex-1 px-3 mt-4 overflow-y-auto no-scrollbar">
        <ul class="space-y-1">

            {{-- DASHBOARD: Generalmente solo para Admin o Cajero si ve reportes --}}
            @role('admin')
                <li>
                    <a href="{{ route('dashboard') }}"
                        class="flex items-center px-3 py-2.5 rounded-xl transition-all duration-200 group
                    {{ request()->routeIs('dashboard') ? 'bg-orange-50 text-orange-600' : 'text-slate-600 hover:bg-slate-50 hover:text-orange-600' }}">

                        <i
                            class="fa-solid fa-chart-pie w-5 text-center 
                        {{ request()->routeIs('dashboard') ? 'text-orange-600' : 'text-slate-400 group-hover:text-orange-600' }}"></i>

                        <div class="ml-3 transition-all duration-200 overflow-hidden whitespace-nowrap"
                            :class="sidebarExpanded ? 'opacity-100 visible w-auto' : 'opacity-0 invisible w-0'">
                            <span class="text-sm {{ request()->routeIs('dashboard') ? 'font-bold' : 'font-medium' }}">
                                Dashboard
                            </span>
                        </div>
                    </a>
                </li>
            @endrole

            {{-- SECCIÓN: ESTACIONES (Visible para Meseros, Cocineros o Cajeros) --}}
            @canany(['mesas.ver', 'ordenes.ver'])
                <li class="pt-5 pb-1" :class="sidebarExpanded ? 'px-3' : 'px-0 text-center'">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-[2px]"
                        x-show="sidebarExpanded">Estaciones</span>
                    <div class="h-px bg-slate-100 w-4 mx-auto" x-show="!sidebarExpanded"></div>
                </li>
            @endcanany

            {{-- MÓDULO: MESERO (Requiere ver mesas o crear órdenes) --}}
            @canany(['mesas.ver', 'ordenes.crear'])
                @php
                    $isWaiterActive = request()->routeIs('tables.*') || request()->routeIs('orders.index');
                @endphp
                <li x-data="{ open: {{ $isWaiterActive ? 'true' : 'false' }} }">
                    <button @click="if(!sidebarExpanded){ sidebarExpanded=true; open=true } else { open=!open }"
                        class="w-full flex items-center px-3 py-2.5 rounded-xl transition-all duration-200 group
                            {{ $isWaiterActive ? 'bg-orange-50 text-orange-600' : 'text-slate-600 hover:bg-slate-50 hover:text-orange-600' }}">

                        <i
                            class="fa-solid fa-concierge-bell w-5 text-center {{ $isWaiterActive ? 'text-orange-600' : 'text-slate-400 group-hover:text-orange-600' }}"></i>

                        <div class="flex flex-1 justify-between ml-3"
                            :class="sidebarExpanded ? '' : 'opacity-0 invisible w-0'">
                            <span class="text-sm {{ $isWaiterActive ? 'font-bold' : 'font-medium' }}">Mesero</span>
                            <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200"
                                :class="open ? 'rotate-180' : ''"></i>
                        </div>
                    </button>

                    <ul x-show="open && sidebarExpanded" x-cloak class="mt-1 ml-4 border-l-2 border-slate-100 space-y-1">
                        @can('mesas.ver')
                            <li>
                                <a href="{{ route('tables.index') }}"
                                    class="flex items-center py-2 pl-6 text-sm transition-colors rounded-r-lg
                                {{ request()->routeIs('tables.*') ? 'text-orange-600 font-semibold bg-orange-50/50' : 'text-slate-500 hover:text-orange-600 hover:bg-slate-50' }}">
                                    <i class="fa-solid fa-chair mr-3 text-[10px]"></i> Salón y Mesas
                                </a>
                            </li>
                        @endcan

                        @can('ordenes.crear')
                            <li>
                                <a href="{{ route('orders.index') }}"
                                    class="flex items-center py-2 pl-6 text-sm transition-colors rounded-r-lg
                                {{ request()->routeIs('orders.index') ? 'text-orange-600 font-semibold bg-orange-50/50' : 'text-slate-500 hover:text-orange-600 hover:bg-slate-50' }}">
                                    <i class="fa-solid fa-receipt mr-3 text-[10px]"></i> Listar Pedidos
                                </a>
                            </li>
                        @endcan
                    </ul>
                </li>
            @endcanany

            {{-- MÓDULO: PREPARACIÓN --}}
            @if (Auth::user()->hasRole('admin') || Auth::user()->preparationStations()->exists())
                @php
                    $isChefActive = request()->routeIs('orders.chef');
                @endphp
                <li>
                    <a href="{{ route('orders.chef') }}"
                        class="flex items-center px-3 py-2.5 rounded-xl transition-all duration-200 group
                        {{ $isChefActive ? 'bg-orange-50 text-orange-600' : 'text-slate-600 hover:bg-slate-50 hover:text-orange-600' }}">

                        <i
                            class="fa-solid fa-fire-burner w-5 text-center 
                            {{ $isChefActive ? 'text-orange-600' : 'text-slate-400 group-hover:text-orange-600' }}"></i>

                        <div class="ml-3 transition-all duration-200 overflow-hidden whitespace-nowrap"
                            :class="sidebarExpanded ? 'opacity-100 visible w-auto' : 'opacity-0 invisible w-0'">
                            <span class="text-sm {{ $isChefActive ? 'font-bold' : 'font-medium' }}">
                                Preparación
                            </span>
                        </div>
                    </a>
                </li>
            @endif

            @canany(['ordenes.cobrar', 'gastos.index'])
                @php
                    $isCashierActive =
                        request()->routeIs('orders.cashier') ||
                        request()->routeIs('boxes.index') ||
                        request()->routeIs('boxes.movements') ||
                        request()->routeIs('expenses.index');
                @endphp

                <li x-data="{ open: {{ $isCashierActive ? 'true' : 'false' }} }">
                    <button @click="if(!sidebarExpanded){ sidebarExpanded=true; open=true } else { open=!open }"
                        class="w-full flex items-center px-3 py-2.5 rounded-xl transition-all duration-200 group
                {{ $isCashierActive ? 'bg-orange-50 text-orange-600' : 'text-slate-600 hover:bg-slate-50 hover:text-orange-600' }}">

                        <i
                            class="fa-solid fa-wallet w-5 text-center 
                {{ $isCashierActive ? 'text-orange-600' : 'text-slate-400 group-hover:text-orange-600' }}"></i>

                        <div class="flex flex-1 justify-between ml-3"
                            :class="sidebarExpanded ? '' : 'opacity-0 invisible w-0'">
                            <span class="text-sm {{ $isCashierActive ? 'font-bold' : 'font-medium' }}">Caja</span>
                            <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200"
                                :class="open ? 'rotate-180' : ''"></i>
                        </div>
                    </button>

                    <ul x-show="open && sidebarExpanded" x-cloak class="mt-1 ml-4 border-l-2 border-slate-100 space-y-1">

                        @can('ordenes.cobrar')
                            <li>
                                <a href="{{ route('boxes.index') }}"
                                    class="flex items-center py-2 pl-6 text-sm transition-colors rounded-r-lg
                    {{ request()->routeIs('boxes.index') ? 'text-orange-600 font-semibold bg-orange-50/50' : 'text-slate-500 hover:text-orange-600 hover:bg-slate-50' }}">
                                    <i class="fa-solid fa-key mr-3 text-[10px]"></i> Turnos y Cierre
                                </a>
                            </li>
                        @endcan

                        @can('ordenes.cobrar')
                            <li>
                                <a href="{{ route('orders.cashier') }}"
                                    class="flex items-center py-2 pl-6 text-sm transition-colors rounded-r-lg
                    {{ request()->routeIs('orders.cashier') ? 'text-orange-600 font-semibold bg-orange-50/50' : 'text-slate-500 hover:text-orange-600 hover:bg-slate-50' }}">
                                    <i class="fa-solid fa-hand-holding-dollar mr-3 text-[10px]"></i> Cuentas Por Cobrar
                                </a>
                            </li>
                        @endcan

                        @can('gastos.ver')
                            <li>
                                <a href="{{ route('expenses.index') }}"
                                    class="flex items-center py-2 pl-6 text-sm transition-colors rounded-r-lg
                    {{ request()->routeIs('expenses.index') ? 'text-orange-600 font-semibold bg-orange-50/50' : 'text-slate-500 hover:text-orange-600 hover:bg-slate-50' }}">
                                    <i class="fa-solid fa-money-bill-wave mr-3 text-[10px]"></i> Gastos
                                </a>
                            </li>
                        @endcan

                    </ul>
                </li>
            @endcanany

            {{-- SECCIÓN: ADMINISTRACIÓN --}}
            @canany(['ventas.ver', 'productos.ver', 'categorias.ver', 'usuarios.ver', 'roles.ver'])
                <li class="pt-5 pb-1" :class="sidebarExpanded ? 'px-3' : 'px-0 text-center'">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-[2px]"
                        x-show="sidebarExpanded">Administración</span>
                    <div class="h-px bg-slate-100 w-4 mx-auto" x-show="!sidebarExpanded"></div>
                </li>
            @endcanany

            {{-- HISTORIAL DE VENTAS --}}
            @can('ventas.ver')
                @php
                    $isSalesActive = request()->routeIs('sales.*');
                @endphp
                <li>
                    <a href="{{ route('sales.index') }}"
                        class="flex items-center px-3 py-2.5 rounded-xl transition-all duration-200 group
                        {{ $isSalesActive ? 'bg-orange-50 text-orange-600' : 'text-slate-600 hover:bg-slate-50 hover:text-orange-600' }}">

                        <i
                            class="fa-solid fa-cash-register w-5 text-center 
                            {{ $isSalesActive ? 'text-orange-600' : 'text-slate-400 group-hover:text-orange-600' }}"></i>

                        <div class="ml-3 transition-all duration-200 overflow-hidden whitespace-nowrap"
                            :class="sidebarExpanded ? 'opacity-100 visible w-auto' : 'opacity-0 invisible w-0'">
                            <span class="text-sm {{ $isSalesActive ? 'font-bold' : 'font-medium' }}">
                                Historial de Ventas
                            </span>
                        </div>
                    </a>
                </li>
            @endcan

            {{-- REPORTE DE PROMOCIONES --}}
            @can('ventas.reportes')
                @php
                    $isPromotionsReportActive = request()->routeIs('reports.promotions');
                @endphp
                <li>
                    <a href="{{ route('reports.promotions') }}"
                        class="flex items-center px-3 py-2.5 rounded-xl transition-all duration-200 group
                        {{ $isPromotionsReportActive ? 'bg-orange-50 text-orange-600' : 'text-slate-600 hover:bg-slate-50 hover:text-orange-600' }}">

                        <i class="fa-solid fa-tags w-5 text-center 
                            {{ $isPromotionsReportActive ? 'text-orange-600' : 'text-slate-400 group-hover:text-orange-600' }}"></i>

                        <div class="ml-3 transition-all duration-200 overflow-hidden whitespace-nowrap"
                            :class="sidebarExpanded ? 'opacity-100 visible w-auto' : 'opacity-0 invisible w-0'">
                            <span class="text-sm {{ $isPromotionsReportActive ? 'font-bold' : 'font-medium' }}">
                                Impacto de Promociones
                            </span>
                        </div>
                    </a>
                </li>
                @php
                    $isProfitReportActive = request()->routeIs('reports.profit');
                @endphp
                <li>
                    <a href="{{ route('reports.profit') }}"
                        class="flex items-center px-3 py-2.5 rounded-xl transition-all duration-200 group
                        {{ $isProfitReportActive ? 'bg-orange-50 text-orange-600' : 'text-slate-600 hover:bg-slate-50 hover:text-orange-600' }}">
                        <i class="fa-solid fa-chart-line w-5 text-center {{ $isProfitReportActive ? 'text-orange-600' : 'text-slate-400 group-hover:text-orange-600' }}"></i>
                        <div class="ml-3 transition-all duration-200 overflow-hidden whitespace-nowrap"
                            :class="sidebarExpanded ? 'opacity-100 visible w-auto' : 'opacity-0 invisible w-0'">
                            <span class="text-sm {{ $isProfitReportActive ? 'font-bold' : 'font-medium' }}">Utilidad</span>
                        </div>
                    </a>
                </li>
            @endcan

            {{-- ALMACÉN (Productos y Categorías) --}}
            @canany(['productos.ver', 'productos.editar', 'categorias.ver'])
                @php
                    $isActiveGestion = request()->routeIs('products.*') || request()->routeIs('categories.*') || request()->routeIs('ingredients.*') || request()->routeIs('purchases.*') || request()->routeIs('promotions.*');
                @endphp
                <li x-data="{ open: {{ $isActiveGestion ? 'true' : 'false' }} }">
                    <button @click="if(!sidebarExpanded){ sidebarExpanded=true; open=true } else { open=!open }"
                        class="w-full flex items-center px-3 py-2.5 rounded-xl transition-all duration-200 group
                            {{ $isActiveGestion ? 'bg-orange-50 text-orange-600' : 'text-slate-600 hover:bg-slate-50 hover:text-orange-600' }}">

                        <i
                            class="fa-solid fa-box w-5 text-center {{ $isActiveGestion ? 'text-orange-600' : 'text-slate-400 group-hover:text-orange-600' }}"></i>

                        <div class="flex flex-1 justify-between ml-3"
                            :class="sidebarExpanded ? '' : 'opacity-0 invisible w-0'">
                            <span class="text-sm {{ $isActiveGestion ? 'font-bold' : 'font-medium' }}">Almacén</span>
                            <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200 self-center"
                                :class="open ? 'rotate-180' : ''"></i>
                        </div>
                    </button>

                    <ul x-show="open && sidebarExpanded" x-cloak class="mt-1 ml-4 border-l border-slate-200 space-y-1">
                        @can('productos.ver')
                            <li>
                                <a href="{{ route('products.index') }}"
                                    class="flex items-center py-2 pl-6 text-sm transition-all duration-200 rounded-r-lg
                                {{ request()->routeIs('products.*') ? 'text-orange-600 font-semibold bg-orange-50/50' : 'text-slate-500 hover:text-orange-600 hover:bg-slate-50' }}">
                                    <i class="fa-solid fa-fish mr-3 text-[10px]"></i> Productos
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('ingredients.index') }}"
                                    class="flex items-center py-2 pl-6 text-sm transition-all duration-200 rounded-r-lg
                                {{ request()->routeIs('ingredients.*') ? 'text-orange-600 font-semibold bg-orange-50/50' : 'text-slate-500 hover:text-orange-600 hover:bg-slate-50' }}">
                                    <i class="fa-solid fa-wheat-awn mr-3 text-[10px]"></i> Insumos
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('promotions.index') }}"
                                    class="flex items-center py-2 pl-6 text-sm transition-all duration-200 rounded-r-lg
                                {{ request()->routeIs('promotions.*') ? 'text-orange-600 font-semibold bg-orange-50/50' : 'text-slate-500 hover:text-orange-600 hover:bg-slate-50' }}">
                                    <i class="fa-solid fa-tags mr-3 text-[10px]"></i> Promociones
                                </a>
                            </li>
                        @endcan

                        @can('productos.editar')
                            <li>
                                <a href="{{ route('purchases.index') }}"
                                    class="flex items-center py-2 pl-6 text-sm transition-all duration-200 rounded-r-lg
                                {{ request()->routeIs('purchases.*') ? 'text-orange-600 font-semibold bg-orange-50/50' : 'text-slate-500 hover:text-orange-600 hover:bg-slate-50' }}">
                                    <i class="fa-solid fa-cart-flatbed mr-3 text-[10px]"></i> Compras
                                </a>
                            </li>
                        @endcan

                        @can('categorias.ver')
                            <li>
                                <a href="{{ route('categories.index') }}"
                                    class="flex items-center py-2 pl-6 text-sm transition-all duration-200 rounded-r-lg
                                {{ request()->routeIs('categories.*') ? 'text-orange-600 font-semibold bg-orange-50/50' : 'text-slate-500 hover:text-orange-600 hover:bg-slate-50' }}">
                                    <i class="fa-solid fa-layer-group mr-3 text-[10px]"></i> Categorías
                                </a>
                            </li>
                        @endcan
                    </ul>
                </li>
            @endcanany

            {{-- CONFIGURACIÓN DEL SISTEMA --}}
            @canany(['usuarios.ver', 'roles.ver', 'empresa.editar', 'payment_methods.ver'])
                @php
                    $activeConfig =
                        request()->routeIs('users.*', 'settings.*', 'payment-methods.*', 'preparation-stations.*', 'restaurant-layout.*') || request()->is('roles*');
                @endphp

                <li x-data="{ open: {{ $activeConfig ? 'true' : 'false' }} }">
                    <button @click="if(!sidebarExpanded){ sidebarExpanded=true; open=true } else { open=!open }"
                        class="w-full flex items-center px-3 py-2.5 rounded-xl transition-all duration-200 group
                        {{ $activeConfig ? 'bg-orange-50 text-orange-600' : 'text-slate-600 hover:bg-slate-50 hover:text-orange-600' }}">

                        <i
                            class="fa-solid fa-gears w-5 text-center {{ $activeConfig ? 'text-orange-600' : 'text-slate-400 group-hover:text-orange-600' }}"></i>

                        <div class="flex flex-1 justify-between ml-3"
                            :class="sidebarExpanded ? '' : 'opacity-0 invisible w-0'">
                            <span class="text-sm {{ $activeConfig ? 'font-bold' : 'font-medium' }}">Ajustes Sistema</span>
                            <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200"
                                :class="open ? 'rotate-180' : ''"></i>
                        </div>
                    </button>

                    <ul x-show="open && sidebarExpanded" x-cloak class="mt-1 ml-4 border-l-2 border-slate-100 space-y-1">
                        @can('usuarios.ver')
                            <li>
                                <a href="{{ route('users.index') }}"
                                    class="flex items-center py-2 pl-6 text-sm rounded-r-lg transition-all
                                    {{ request()->routeIs('users.index') ? 'text-orange-600 font-bold bg-orange-50/50' : 'text-slate-500 hover:text-orange-600' }}">
                                    <i class="fa-solid fa-user mr-3 text-[10px]"></i> Usuarios
                                </a>
                            </li>
                        @endcan

                        @can('roles.ver')
                            <li>
                                <a href="{{ route('users.role') }}"
                                    class="flex items-center py-2 pl-6 text-sm rounded-r-lg transition-all
                                    {{ request()->routeIs('users.role') ? 'text-orange-600 font-bold bg-orange-50/50' : 'text-slate-500 hover:text-orange-600' }}">
                                    <i class="fa-solid fa-lock mr-3 text-[10px]"></i> Roles
                                </a>
                            </li>
                        @endcan

                        @can('empresa.editar')
                            <li>
                                <a href="{{ route('preparation-stations.index') }}"
                                    class="flex items-center py-2 pl-6 text-sm rounded-r-lg transition-all
                                    {{ request()->routeIs('preparation-stations.*') ? 'text-orange-600 font-bold bg-orange-50/50' : 'text-slate-500 hover:text-orange-600' }}">
                                    <i class="fa-solid fa-fire-burner mr-3 text-[10px]"></i> Estaciones
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('settings.index') }}"
                                    class="flex items-center py-2 pl-6 text-sm rounded-r-lg transition-all
                                    {{ request()->routeIs('settings.*') ? 'text-orange-600 font-bold bg-orange-50/50' : 'text-slate-500 hover:text-orange-600' }}">
                                    <i class="fa-solid fa-building mr-3 text-[10px]"></i> Empresa
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('restaurant-layout.index') }}"
                                    class="flex items-center py-2 pl-6 text-sm rounded-r-lg transition-all
                                    {{ request()->routeIs('restaurant-layout.*') ? 'text-orange-600 font-bold bg-orange-50/50' : 'text-slate-500 hover:text-orange-600' }}">
                                    <i class="fa-solid fa-layer-group mr-3 text-[10px]"></i> Pisos y zonas
                                </a>
                            </li>
                        @endcan

                        @can('payment_methods.ver')
                            <li>
                                <a href="{{ route('payment-methods.index') }}"
                                    class="flex items-center py-2 pl-6 text-sm rounded-r-lg transition-all
                                    {{ request()->routeIs('payment-methods.*') ? 'text-orange-600 font-bold bg-orange-50/50' : 'text-slate-500 hover:text-orange-600' }}">
                                    <i class="fa-solid fa-credit-card mr-3 text-[10px]"></i> Pagos
                                </a>
                            </li>
                        @endcan
                    </ul>
                </li>
            @endcanany
        </ul>
    </nav>

    <div
        class="sticky bottom-0 shrink-0 p-4 border-t border-slate-100 bg-slate-50/80 backdrop-blur-sm overflow-hidden">
        <div class="flex items-center gap-3">
            <div
                class="w-10 h-10 rounded-full bg-orange-600 flex items-center justify-center text-white font-bold shrink-0 shadow-md shadow-orange-200">
                {{ substr(Auth::user()->name, 0, 1) }}
            </div>
            <div class="flex-1 transition-opacity duration-300"
                :class="sidebarExpanded ? 'opacity-100' : 'opacity-0 invisible w-0'">
                <p class="text-sm font-bold text-slate-800 truncate">{{ Auth::user()->name }}</p>
                <p class="text-xs text-slate-500 truncate">{{ Auth::user()->email }}</p>
            </div>
        </div>
    </div>
</div>
