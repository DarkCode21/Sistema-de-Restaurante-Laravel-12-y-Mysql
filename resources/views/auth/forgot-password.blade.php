<x-guest-layout>
    <style>
        .brand-side {
            /* Colores gastronómicos: naranjas, fuegos y ambar */
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
                <div class="bg-white/20 p-4 md:p-6 rounded-3xl inline-block mb-3 md:mb-6 backdrop-blur-md border border-white/30 shadow-2xl">
                    <i class="fas fa-utensils text-3xl md:text-5xl text-white"></i>
                </div>
                <h2 class="text-2xl md:text-4xl font-black mb-2 tracking-tight">{{ $empresa->company_name }}</h2>
                <p class="text-xs md:text-sm font-medium text-orange-100 opacity-90 tracking-wide uppercase">Portal de Equipo</p>
            </div>
        </div>

        <div class="flex items-center justify-center bg-white p-6 sm:p-10 md:p-16">
            <div class="w-full max-w-md">
                <div class="mb-8 text-center md:text-left">
                    <h2 class="text-2xl md:text-3xl font-bold text-slate-800 tracking-tight">¿Olvidaste tu contraseña?</h2>
                    <p class="mt-4 text-sm text-slate-500 leading-relaxed italic">
                        {{ __('No te preocupes. Introduce el correo electrónico con el que estás registrado y te enviaremos las instrucciones para recuperarla.') }}
                    </p>
                </div>

                <x-auth-session-status class="mb-6 p-4 bg-emerald-50 text-emerald-700 rounded-xl border border-emerald-100 text-sm font-medium" :status="session('status')" />

                <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
                    @csrf

                    <div class="input-group">
                        <label for="email" class="block text-[10px] font-bold uppercase tracking-[2px] text-orange-600 mb-2 ml-1">
                            {{ __('Correo Electrónico') }}
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fas fa-envelope text-orange-400 text-sm"></i>
                            </div>
                            <input id="email" 
                                   class="block w-full pl-11 pr-4 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all text-sm md:text-base text-slate-700 shadow-sm placeholder:text-slate-400 placeholder:italic" 
                                   type="email" name="email" :value="old('email')" 
                                   required autofocus placeholder="tu-correo@restaurante.com" />
                        </div>
                        <x-input-error :messages="$errors->get('email')" class="mt-2 text-rose-500 text-xs font-bold" />
                    </div>

                    <div class="flex flex-col space-y-4 pt-2">
                    
                        <button type="submit" class="w-full flex justify-center py-4 px-4 border border-transparent text-xs font-black rounded-2xl text-white bg-slate-900 hover:bg-orange-600 shadow-xl transform transition active:scale-95 uppercase tracking-widest">
                            {{ __('ENVIAR ENLACE DE RECUPERACIÓN') }}
                        </button>

                        <a class="text-center text-[10px] font-bold text-slate-400 hover:text-orange-600 transition-colors uppercase tracking-[2px] py-2" href="{{ route('login') }}">
                            <i class="fas fa-chevron-left text-[8px] mr-1"></i> {{ __('Regresar al Inicio de Sesión') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-guest-layout>