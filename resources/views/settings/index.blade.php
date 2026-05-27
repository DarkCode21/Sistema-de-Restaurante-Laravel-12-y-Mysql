<x-admin-layout>
    <div class="min-h-screen">
        <div class="max-w-7xl mx-auto">

            <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="font-extrabold text-slate-900 tracking-tight flex items-center gap-3">
                        <i class="fas fa-cogs text-orange-600"></i>
                        Configuración del Sistema
                    </h2>
                    <p class="text-slate-500 text-sm md:text-base mt-1">Administra la identidad visual y los parámetros
                        globales.</p>
                </div>
            </div>

            @if (session('success'))
                <div
                    class="mb-6 p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-700 rounded-r-xl shadow-sm text-sm flex items-center gap-3">
                    <i class="fas fa-check-circle"></i>
                    {{ session('success') }}
                </div>
            @endif

            <form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                    <div class="lg:col-span-2 space-y-6">

                        <div class="bg-white p-6 md:p-8 shadow-sm border border-slate-200">
                            <h3 class="text-lg font-bold text-slate-800 mb-6 flex items-center gap-2">
                                <i class="fas fa-building text-orange-500"></i> Información de Empresa
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div class="md:col-span-2 space-y-1">
                                    <label
                                        class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Nombre
                                        Comercial</label>
                                    <input type="text" name="company_name"
                                        value="{{ old('company_name', $setting->company_name) }}"
                                        class="w-full rounded-xl @error('company_name') border-red-500 focus:ring-red-500/10 focus:border-red-500 @else border-slate-200 focus:ring-orange-500/10 focus:border-orange-500 @enderror transition-all font-medium">
                                    @error('company_name')
                                        <p class="text-xs text-red-500 font-semibold mt-1 ml-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="space-y-1">
                                    <label
                                        class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Email
                                        de Soporte</label>
                                    <input type="email" name="company_email"
                                        value="{{ old('company_email', $setting->company_email) }}"
                                        class="w-full rounded-xl @error('company_email') border-red-500 focus:ring-red-500/10 focus:border-red-500 @else border-slate-200 focus:ring-orange-500/10 focus:border-orange-500 @enderror transition-all font-medium">
                                    @error('company_email')
                                        <p class="text-xs text-red-500 font-semibold mt-1 ml-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="space-y-1">
                                    <label
                                        class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Teléfono</label>
                                    <input type="text" name="company_phone"
                                        value="{{ old('company_phone', $setting->company_phone) }}"
                                        class="w-full rounded-xl @error('company_phone') border-red-500 focus:ring-red-500/10 focus:border-red-500 @else border-slate-200 focus:ring-orange-500/10 focus:border-orange-500 @enderror transition-all font-medium">
                                    @error('company_phone')
                                        <p class="text-xs text-red-500 font-semibold mt-1 ml-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="space-y-1">
                                    <label
                                        class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Moneda
                                        Simbolo</label>
                                    <input type="text" name="currency_simbol"
                                        value="{{ old('currency_simbol', $setting->currency_simbol) }}"
                                        class="w-full rounded-xl @error('currency_simbol') border-red-500 focus:ring-red-500/10 focus:border-red-500 @else border-slate-200 focus:ring-orange-500/10 focus:border-orange-500 @enderror transition-all font-medium">
                                    @error('currency_simbol')
                                        <p class="text-xs text-red-500 font-semibold mt-1 ml-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="space-y-1">
                                    <label
                                        class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Tax
                                        ID / RUC</label>
                                    <input type="text" name="tax_id" value="{{ old('tax_id', $setting->tax_id) }}"
                                        class="w-full rounded-xl @error('tax_id') border-red-500 focus:ring-red-500/10 focus:border-red-500 @else border-slate-200 focus:ring-orange-500/10 focus:border-orange-500 @enderror transition-all font-medium">
                                    @error('tax_id')
                                        <p class="text-xs text-red-500 font-semibold mt-1 ml-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="md:col-span-2 space-y-1">
                                    <label
                                        class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Dirección
                                        Principal</label>
                                    <textarea name="company_address" rows="2"
                                        class="w-full rounded-xl @error('company_address') border-red-500 focus:ring-red-500/10 focus:border-red-500 @else border-slate-200 focus:ring-orange-500/10 focus:border-orange-500 @enderror transition-all font-medium">{{ old('company_address', $setting->company_address) }}</textarea>
                                    @error('company_address')
                                        <p class="text-xs text-red-500 font-semibold mt-1 ml-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="bg-white p-6 md:p-8 shadow-sm border border-slate-200" x-data="{
                            networks: {{ json_encode($setting->social_networks ?? ['facebook' => '']) }},
                            addNetwork() {
                                let name = prompt('Nombre de la red (ej: instagram, tiktok):');
                                if (name) this.networks[name.toLowerCase()] = '';
                            },
                            removeNetwork(key) {
                                delete this.networks[key];
                            }
                        }">
                            <div class="flex justify-between items-center mb-6">
                                <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                                    <i class="fas fa-share-nodes text-orange-500"></i> Redes Sociales
                                </h3>
                                <button type="button" @click="addNetwork()"
                                    class="text-[10px] font-black bg-orange-50 text-orange-600 px-4 py-2 rounded-xl hover:bg-orange-100 transition-colors uppercase tracking-widest">
                                    <i class="fas fa-plus mr-1"></i> Agregar Red
                                </button>
                            </div>

                            <div class="grid grid-cols-1 gap-4">
                                <template x-for="(value, key) in networks" :key="key">
                                    <div
                                        class="flex flex-col sm:flex-row items-center gap-3 p-4 bg-slate-50/50 rounded-2xl border border-slate-100 group transition-all hover:bg-white hover:shadow-md hover:shadow-slate-100">
                                        <div class="w-full sm:w-32 flex items-center gap-2">
                                            <div
                                                class="w-8 h-8 rounded-lg bg-white shadow-sm flex items-center justify-center text-orange-500">
                                                <i
                                                    :class="'fab fa-' + (['facebook', 'instagram', 'whatsapp', 'twitter',
                                                        'linkedin', 'youtube', 'tiktok'
                                                    ].includes(key) ? key : 'link')"></i>
                                            </div>
                                            <span
                                                class="text-[11px] font-black text-slate-400 uppercase tracking-tighter truncate"
                                                x-text="key"></span>
                                        </div>
                                        <div class="relative flex-1 w-full">
                                            <input type="url" :name="'social_networks[' + key + ']'"
                                                x-model="networks[key]" placeholder="https://..."
                                                class="w-full bg-white rounded-xl border-slate-200 text-sm focus:ring-4 focus:ring-orange-500/10 transition-all font-medium">
                                        </div>
                                        <button type="button" @click="removeNetwork(key)"
                                            class="p-2 text-slate-300 hover:text-red-500 transition-colors">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="bg-white p-6 md:p-8 shadow-sm border border-slate-200">
                            <h3 class="text-lg font-bold text-slate-800 mb-8 flex items-center gap-2">
                                <i class="fas fa-palette text-orange-500"></i> Identidad Visual
                            </h3>

                            <div class="mb-10">
                                <label
                                    class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">Logo
                                    Principal</label>
                                <div class="relative group">
                                    <div
                                        class="w-full h-32 bg-slate-50 rounded-2xl border-2 border-dashed @error('logo_path') border-red-500 bg-red-50/10 @else border-slate-200 group-hover:border-orange-400 group-hover:bg-orange-50/30 @enderror transition-all flex flex-col items-center justify-center p-4">
                                        <img id="logo-preview"
                                            src="{{ $setting->logo_path ? asset('storage/' . $setting->logo_path) : 'https://ui-avatars.com/api/?name=Logo' }}"
                                            class="max-h-full object-contain drop-shadow-sm">
                                        <input type="file" name="logo_path"
                                            onchange="previewImage(this, 'logo-preview')"
                                            class="absolute inset-0 opacity-0 cursor-pointer">
                                        <div
                                            class="absolute bottom-2 right-2 bg-white w-8 h-8 rounded-full shadow-md flex items-center justify-center text-slate-400 group-hover:text-orange-600 transition-colors">
                                            <i class="fas fa-camera text-xs"></i>
                                        </div>
                                    </div>
                                </div>
                                @error('logo_path')
                                    <p class="text-xs text-red-500 font-semibold mt-2 ml-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="mb-10">
                                <label
                                    class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">Favicon
                                    (32x32px)</label>
                                <div
                                    class="flex items-center gap-5 p-4 bg-slate-50 rounded-2xl border @error('favicon_path') border-red-500 @else border-slate-100 @enderror relative group">
                                    <div
                                        class="shrink-0 w-14 h-14 bg-white rounded-xl shadow-sm border border-slate-200 flex items-center justify-center overflow-hidden">
                                        <img id="favicon-preview"
                                            src="{{ $setting->favicon_path ? asset('storage/' . $setting->favicon_path) : 'https://ui-avatars.com/api/?name=F' }}"
                                            class="w-8 h-8 object-contain">
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-[11px] font-bold text-slate-600">Cambiar icono</p>
                                        <p class="text-[9px] text-slate-400 font-medium">Formatos: .ico, .png</p>
                                    </div>
                                    <input type="file" name="favicon_path"
                                        onchange="previewImage(this, 'favicon-preview')"
                                        class="absolute inset-0 opacity-0 cursor-pointer">
                                    <div class="w-8 h-8 flex items-center justify-center text-orange-500">
                                        <i class="fas fa-cloud-arrow-up"></i>
                                    </div>
                                </div>
                                @error('favicon_path')
                                    <p class="text-xs text-red-500 font-semibold mt-2 ml-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <hr class="my-8 border-slate-100">

                            <div class="space-y-6">
                                <div class="space-y-3">
                                    <label
                                        class="text-[10px] font-black text-slate-400 uppercase tracking-widest flex items-center gap-2">
                                        <i class="fas fa-clock"></i> Zona Horaria Local
                                    </label>
                                    <div class="relative">
                                        <div
                                            class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                                            <i class="fas fa-globe-americas text-xs"></i>
                                        </div>
                                        <select name="timezone"
                                            class="w-full pl-10 pr-4 py-3 bg-slate-50 border @error('timezone') border-red-500 @else border-slate-200 focus:border-orange-500 @enderror rounded-xl focus:ring-4 focus:ring-orange-500/10 transition-all text-sm font-semibold text-slate-700 appearance-none">
                                            @foreach (timezone_identifiers_list() as $tz)
                                                <option value="{{ $tz }}"
                                                    {{ old('timezone', $setting->timezone) == $tz ? 'selected' : '' }}>
                                                    {{ $tz }}</option>
                                            @endforeach
                                        </select>
                                        <div
                                            class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-slate-400">
                                            <i class="fas fa-chevron-down text-[10px]"></i>
                                        </div>
                                    </div>
                                    @error('timezone')
                                        <p class="text-xs text-red-500 font-semibold mt-1 ml-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="space-y-4 pt-4 border-t border-slate-100" x-data="{
                                    directPrint: {{ old('direct_printing', $setting->direct_printing) || $errors->has('printer_name') || $errors->has('kitchen_printer_name') ? 'true' : 'false' }}
                                }">

                                    <label
                                        class="text-[10px] font-black text-slate-400 uppercase tracking-widest flex items-center gap-2">
                                        <i class="fas fa-print"></i> Configuración de Impresión
                                    </label>

                                    <div
                                        class="flex items-center justify-between p-3 bg-slate-50 rounded-xl border border-slate-100">
                                        <div class="flex flex-col">
                                            <span class="text-xs font-bold text-slate-700">Impresión Directa</span>
                                            <span class="text-[10px] text-slate-400 font-medium">
                                                Saltar ventana de vista previa
                                            </span>
                                        </div>

                                        <input type="hidden" name="direct_printing" value="0">

                                        <label class="relative inline-flex items-center cursor-pointer select-none">
                                            <input type="checkbox" name="direct_printing" value="1"
                                                x-model="directPrint" class="sr-only peer">

                                            <div
                                                class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-600">
                                            </div>
                                        </label>
                                    </div>

                                    <div class="space-y-1 transition-all duration-300" x-show="directPrint"
                                        x-transition x-cloak>

                                        <label
                                            class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">
                                            Impresora Principal
                                        </label>

                                        <div class="relative">
                                            <div
                                                class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                                                <i class="fas fa-receipt text-xs"></i>
                                            </div>

                                            <input type="text" name="printer_name"
                                                value="{{ old('printer_name', $setting->printer_name) }}"
                                                placeholder="Ej: Ticketera_Principal"
                                                class="w-full pl-10 rounded-xl @error('printer_name') border-red-500 focus:ring-red-500/10 focus:border-red-500 @else border-slate-200 focus:ring-orange-500/10 focus:border-orange-500 @enderror transition-all text-sm font-medium">
                                        </div>

                                        @error('printer_name')
                                            <p class="text-xs text-red-500 font-semibold mt-1 ml-1">
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>

                                    <div class="space-y-1 transition-all duration-300" x-show="directPrint"
                                        x-transition x-cloak>

                                        <label
                                            class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">
                                            Impresora Cocina
                                        </label>

                                        <div class="relative">
                                            <div
                                                class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                                                <i class="fas fa-receipt text-xs"></i>
                                            </div>

                                            <input type="text" name="kitchen_printer_name"
                                                value="{{ old('kitchen_printer_name', $setting->kitchen_printer_name) }}"
                                                placeholder="Ej: Ticketera_Cocina"
                                                class="w-full pl-10 rounded-xl @error('kitchen_printer_name') border-red-500 focus:ring-red-500/10 focus:border-red-500 @else border-slate-200 focus:ring-orange-500/10 focus:border-orange-500 @enderror transition-all text-sm font-medium">
                                        </div>

                                        @error('kitchen_printer_name')
                                            <p class="text-xs text-red-500 font-semibold mt-1 ml-1">
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="submit"
                            class="w-full bg-slate-900 text-white font-bold py-3 rounded-3xl shadow-2xl shadow-slate-200 hover:bg-black hover:-translate-y-1 transition-all duration-300 flex items-center justify-center gap-3 tracking-widest text-xs">
                            <i class="fas fa-save"></i>
                            GUARDAR CAMBIOS
                        </button>
                    </div>

                </div>
            </form>
        </div>
    </div>

    <script>
        function previewImage(input, previewId) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById(previewId).setAttribute('src', e.target.result);
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</x-admin-layout>
