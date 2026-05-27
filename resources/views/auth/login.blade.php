<x-guest-layout>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;1,700&family=Plus+Jakarta+Sans:wght@400;500;600;800&display=swap');

        .brand-side {
            background-image: url("{{ asset('images/bg.png') }}");
            background-size: cover;
            background-position: center;
            position: relative;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .brand-content {
            position: relative;
            z-index: 2;
        }

        .font-serif {
            font-family: 'Playfair Display', serif;
        }

        .float-icon {
            animation: floating 4s ease-in-out infinite;
            filter: drop-shadow(0 10px 15px rgba(0, 0, 0, 0.25));
        }

        @keyframes floating {
            0%, 100% {
                transform: translateY(0px) rotate(0deg);
            }
            50% {
                transform: translateY(-15px) rotate(2deg);
            }
        }

        .custom-input:focus {
            border-color: #ea580c;
            box-shadow: 0 0 0 4px rgba(234, 88, 12, 0.1);
        }
    </style>

    <div class="flex flex-col md:grid md:grid-cols-2 min-h-screen w-full bg-[#fcfcfd]">

        <div class="brand-side flex flex-col justify-center items-center text-white p-6 sm:p-8 md:p-12 h-96 md:h-auto">
            <div class="brand-content w-full max-w-md bg-slate-900/40 p-6 md:p-10 rounded-3xl border border-white/20 text-center shadow-[0_25px_50px_-12px_rgba(0,0,0,0.3)]">
                <div class="mb-6 float-icon">
                    <div class="bg-white/20 p-5 rounded-full inline-block backdrop-blur-xl border border-white/30 shadow-md">
                        <i class="fas fa-utensils text-4xl md:text-5xl text-orange-400 drop-shadow"></i>
                    </div>
                </div>

                <h2 class="text-4xl md:text-5xl font-serif italic mb-2 tracking-tight text-white drop-shadow-[0_2px_4px_rgba(0,0,0,0.4)]">
                    {{ $empresa->company_name }}
                </h2>
                <p class="text-orange-100 font-semibold text-xs md:text-sm tracking-widest uppercase drop-shadow-[0_1px_2px_rgba(0,0,0,0.4)]">
                    Sistema de Gestión Gastronómica
                </p>

                <div class="mt-8 flex gap-4 justify-center">
                    <span class="flex items-center px-4 py-2 bg-black/30 text-white rounded-full text-xs font-semibold backdrop-blur-md border border-white/10 shadow-sm">
                        <span class="w-2 h-2 bg-green-400 rounded-full mr-2 animate-pulse"></span>
                        Cocina activa
                    </span>
                    <span class="flex items-center px-4 py-2 bg-black/30 text-white rounded-full text-xs font-semibold backdrop-blur-md border border-white/10 shadow-sm">
                        <i class="fas fa-fire mr-2 text-orange-400"></i> Alta Cocina
                    </span>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-center p-6 sm:p-10 md:p-16">
            <div class="w-full max-w-md">
                <div class="mb-10 text-center md:text-left">
                    <h2 class="text-4xl font-extrabold text-slate-800 tracking-tighter">Iniciar Sesión</h2>
                    <p class="text-slate-400 mt-2 text-sm font-medium">Control total de tu sabor y frescura.</p>
                </div>

                <x-auth-session-status class="mb-4" :status="session('status')" />

                <form method="POST" action="{{ route('login') }}" class="space-y-6">
                    @csrf

                    <div>
                        <label for="email" class="block text-[11px] font-black uppercase tracking-[0.15em] text-slate-500 mb-2 ml-1">
                            Acceso de Usuario
                        </label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none transition-colors group-focus-within:text-orange-600">
                                <i class="fas fa-user-circle text-lg text-slate-400 group-focus-within:text-orange-500"></i>
                            </div>
                            <input id="email" class="custom-input block w-full pl-12 pr-4 py-4 bg-white border border-slate-200 rounded-2xl transition-all text-slate-700 shadow-sm placeholder:text-slate-300" type="email" name="email" :value="old('email')" required autofocus placeholder="usuario@restaurante.com" />
                        </div>
                    </div>

                    <div>
                        <div class="flex justify-between items-center mb-2 ml-1">
                            <label for="password" class="block text-[11px] font-black uppercase tracking-[0.15em] text-slate-500">
                                Contraseña
                            </label>
                        </div>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none transition-colors group-focus-within:text-orange-600">
                                <i class="fas fa-key text-lg text-slate-400 group-focus-within:text-orange-500"></i>
                            </div>
                            <input id="password" class="custom-input block w-full pl-12 pr-4 py-4 bg-white border border-slate-200 rounded-2xl transition-all text-slate-700 shadow-sm placeholder:text-slate-300" type="password" name="password" placeholder="••••••••" required />
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <label for="remember_me" class="flex items-center cursor-pointer">
                            <input id="remember_me" type="checkbox" class="rounded-md border-slate-300 text-orange-600 shadow-sm focus:ring-orange-500 w-5 h-5 transition-all" name="remember">
                            <span class="ms-2 text-sm text-slate-500 font-medium select-none italic">Mantener sesión</span>
                        </label>

                        @if (Route::has('password.request'))
                            <a class="text-xs font-bold text-orange-600 hover:text-orange-500 transition-colors underline underline-offset-4" href="{{ route('password.request') }}">
                                ¿Olvidaste tus datos?
                            </a>
                        @endif
                    </div>

                    <div class="pt-4">
                        <button type="submit" class="w-full flex justify-center items-center py-4 px-4 border border-transparent text-sm font-black rounded-2xl text-white bg-orange-600 hover:bg-orange-700 shadow-[0_10px_25px_-5px_rgba(234,88,12,0.4)] transform transition hover:-translate-y-0.5 active:scale-95 uppercase tracking-wider">
                            <span>Ingresar al Sistema</span>
                            <i class="fas fa-chevron-right ml-3 text-[10px]"></i>
                        </button>
                    </div>
                </form>

                <p class="mt-12 text-center text-slate-400 text-xs">
                    &copy; {{ date('Y') }} Sistema de Gestión de Sabor. Todos los derechos reservados.
                </p>
            </div>
        </div>
    </div>
</x-guest-layout>