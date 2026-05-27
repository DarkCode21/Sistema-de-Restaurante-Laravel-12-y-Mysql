<nav class="sticky top-0 z-30 bg-white border-b border-gray-200 h-20 flex items-center px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between w-full">
        
        <div class="flex items-center gap-2 md:gap-4">
            
            <button @click.stop="sidebarOpen = !sidebarOpen" 
                    class="lg:hidden text-gray-500 hover:text-orange-600 p-2 rounded-lg hover:bg-gray-100 transition-colors">
                <i class="fa-solid fa-bars text-xl"></i>
            </button>

            <button @click.stop="sidebarExpanded = !sidebarExpanded" 
                    class="hidden lg:inline-flex text-gray-500 hover:text-orange-600 p-2 rounded-lg hover:bg-gray-100 transition-all duration-300">
                <i class="fa-solid fa-angles-left transition-transform duration-300"
                   :class="sidebarExpanded ? 'rotate-0' : 'rotate-180'"></i>
            </button>
        
            <h1 class="text-lg md:text-xl font-bold text-gray-800 truncate">
                {{ $header ?? 'Panel Administrativo' }}
            </h1>
        </div>

        <div class="flex items-center gap-2 md:gap-4">
            
            {{-- <button class="hidden sm:flex text-gray-400 hover:text-orange-600 p-2 transition-colors">
                <i class="fa-regular fa-bell text-lg"></i>
            </button> --}}

            <div class="hidden sm:block w-px h-6 bg-gray-200 mx-1"></div>

            <x-dropdown align="right" width="48">
                <x-slot name="trigger">
                    <button class="flex items-center gap-3 hover:bg-gray-50 p-1 md:p-2 rounded-xl transition duration-150 border border-transparent hover:border-gray-200">
                        <div class="relative">
                            <img class="h-9 w-9 rounded-full object-cover border-2 border-orange-100" 
                                 src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&color=7F9CF5&background=EBF4FF&bold=true" 
                                 alt="{{ Auth::user()->name }}">
                            <span class="absolute bottom-0 right-0 block h-2.5 w-2.5 rounded-full bg-green-400 ring-2 ring-white"></span>
                        </div>
                        <div class="hidden md:block text-left">
                            <p class="text-sm font-bold text-gray-700 leading-none">{{ Auth::user()->name }}</p>
                            <p class="text-[11px] text-gray-400 mt-1">En línea</p>
                        </div>
                        <i class="fa-solid fa-chevron-down text-[10px] text-gray-400 hidden md:block"></i>
                    </button>
                </x-slot>

                <x-slot name="content">
                    <div class="px-4 py-2 text-xs text-gray-400 border-b border-gray-100">
                        Administrar Cuenta
                    </div>
                    
                    <x-dropdown-link :href="route('profile.edit')" class="flex items-center gap-2"> 
                        <i class="fa-regular fa-user w-4"></i> Mi Perfil 
                    </x-dropdown-link>
                    
                    <div class="border-t border-gray-100"></div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-dropdown-link :href="route('logout')" 
                                        class="flex items-center gap-2 text-red-600 hover:text-red-700"
                                        onclick="event.preventDefault(); this.closest('form').submit();">
                            <i class="fa-solid fa-arrow-right-from-bracket w-4"></i> Cerrar Sesión
                        </x-dropdown-link>
                    </form>
                </x-slot>
            </x-dropdown>
        </div>
    </div>
</nav>