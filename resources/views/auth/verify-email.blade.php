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
                <p class="text-orange-100/90 text-xs md:text-base font-medium uppercase tracking-widest">Verificación de Cuenta</p>
            </div>
        </div>

        <div class="flex items-center justify-center bg-white p-6 sm:p-10 md:p-16">
            <div class="w-full max-w-md">
                <div class="mb-8 text-center md:text-left">
                    <h2 class="text-2xl md:text-3xl font-bold text-gray-800 tracking-tight">Verifica tu correo</h2>
                    <p class="mt-4 text-sm text-gray-500 leading-relaxed">
                        {{ __('¡Gracias por unirte al equipo! Por favor, verifica tu dirección haciendo clic en el enlace que enviamos. Si no llegó, podemos enviarte otro.') }}
                    </p>
                </div>

                @if (session('status') == 'verification-link-sent')
                    <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 text-xs md:text-sm font-medium text-green-700 shadow-sm">
                        {{ __('Se ha enviado un nuevo enlace de verificación a tu dirección de correo.') }}
                    </div>
                @endif

                <div class="mt-8 flex flex-col space-y-6">
                    <form method="POST" action="{{ route('verification.send') }}">
                        @csrf
                        <button type="submit" class="w-full flex justify-center py-4 px-4 border border-transparent text-xs font-black rounded-2xl text-white bg-orange-600 hover:bg-orange-700 shadow-[0_10px_20px_-5px_rgba(234,88,12,0.3)] transform transition active:scale-95 uppercase tracking-widest">
                            {{ __('Reenviar Email') }}
                        </button>
                    </form>

                    <form method="POST" action="{{ route('logout') }}" class="text-center">
                        @csrf
                        <button type="submit" class="underline text-xs text-gray-400 hover:text-orange-600 font-bold transition uppercase tracking-widest">
                            {{ __('Cerrar Sesión') }}
                        </button>
                    </form>
                </div>

                <div class="mt-12 text-center">
                    <p class="text-[9px] md:text-[10px] text-gray-400 uppercase tracking-widest font-bold">
                        <i class="fas fa-check-circle mr-1 text-orange-400"></i> Paso Final de Validación
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>