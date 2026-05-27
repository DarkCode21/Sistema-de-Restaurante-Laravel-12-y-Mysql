<x-guest-layout>
    <style>
        .brand-side {
            background: linear-gradient(-45deg, #ea580c, #f97316, #f59e0b, #b45309);
            background-size: 400% 400%;
            animation: gradient-slow 15s ease infinite;
        }
        @keyframes gradient-slow {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
    </style>

    <div class="flex flex-col md:grid md:grid-cols-2 min-h-screen w-full bg-white">
        
        <div class="brand-side flex flex-col justify-center items-center text-white p-8 md:p-12 h-48 md:h-auto">
            <div class="text-center">
                <div class="bg-white/20 p-4 md:p-6 rounded-3xl inline-block mb-3 md:mb-6 backdrop-blur-md border border-white/30 shadow-lg">
                    <i class="fas fa-utensils text-3xl md:text-5xl"></i>
                </div>
                <h2 class="text-2xl md:text-4xl font-black mb-1 md:mb-2 tracking-tight">{{ $empresa->company_name }}</h2>
                <p class="text-orange-100/90 text-xs md:text-sm font-medium uppercase tracking-widest">Área Protegida</p>
            </div>
        </div>

        <div class="flex items-center justify-center bg-white p-6 sm:p-10 md:p-16">
            <div class="w-full max-w-md">
                <div class="mb-8 text-center md:text-left">
                    <h2 class="text-2xl md:text-3xl font-bold text-gray-800 tracking-tight">Confirmar Acceso</h2>
                    <p class="mt-4 text-sm text-gray-500 leading-relaxed">
                        {{ __('Esta es una zona reservada. Por favor, confirma tu contraseña antes de continuar.') }}
                    </p>
                </div>

                <form method="POST" action="{{ route('password.confirm') }}" class="space-y-6">
                    @csrf

                    <div class="input-group">
                        <label for="password" class="block text-[10px] md:text-xs font-bold uppercase tracking-wider text-orange-600 mb-2 ml-1">
                            {{ __('Contraseña') }}
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fas fa-key text-orange-400"></i>
                            </div>
                            <input id="password" 
                                   class="block w-full pl-11 pr-4 py-3 md:py-3.5 bg-gray-50 border border-orange-200 rounded-2xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all text-sm md:text-base text-gray-700 shadow-sm"
                                   type="password" 
                                   name="password" 
                                   required 
                                   autocomplete="current-password"
                                   placeholder="••••••••" />
                        </div>
                        <x-input-error :messages="$errors->get('password')" class="mt-2 text-xs text-red-500" />
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="w-full flex justify-center py-4 px-4 border border-transparent text-xs font-black rounded-2xl text-white bg-orange-600 hover:bg-orange-700 shadow-[0_10px_20px_-5px_rgba(234,88,12,0.3)] transform transition hover:-translate-y-0.5 active:scale-95 uppercase tracking-widest">
                            {{ __('Confirmar Identidad') }}
                        </button>
                    </div>
                </form>

                <div class="mt-10 md:mt-12 text-center">
                    <p class="text-[9px] md:text-[10px] text-gray-400 uppercase tracking-widest font-bold">
                        <i class="fas fa-lock mr-1 text-orange-400"></i> Verificación Requerida
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>