@props(['model', 'options', 'placeholder' => 'Seleccionar', 'icon' => 'fa-solid fa-chevron-down'])

<div x-data="{ open: false, value: @entangle($model).live, matches(option) { return option.toLowerCase().includes((this.value || '').toLowerCase()) } }" class="relative">
    <input x-model.debounce.200ms="value" @focus="open = true" @keydown.escape="open = false" type="search" autocomplete="off"
        placeholder="{{ $placeholder }}" role="combobox" :aria-expanded="open"
        class="w-full rounded-xl border-slate-200 bg-white py-2 pl-9 pr-9 text-sm focus:border-orange-500 focus:ring-orange-500/20">
    <i class="fa-solid {{ $icon }} pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400"></i>
    <button type="button" @click="open = !open" class="absolute right-1 top-1/2 -translate-y-1/2 rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Mostrar opciones">
        <i class="fa-solid fa-chevron-down text-[10px]" :class="open && 'rotate-180'"></i>
    </button>
    <div x-cloak x-show="open" x-transition @click.outside="open = false" class="absolute z-30 mt-1 max-h-56 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white p-1 shadow-xl">
        <button type="button" @mousedown.prevent="value = ''; open = false" class="block w-full rounded-lg px-3 py-2 text-left text-sm text-slate-500 hover:bg-slate-50">{{ $placeholder }}</button>
        @foreach ($options as $option)
            <button type="button" x-show="matches({{ \Illuminate\Support\Js::from($option) }})" @mousedown.prevent="value = {{ \Illuminate\Support\Js::from($option) }}; open = false" class="block w-full rounded-lg px-3 py-2 text-left text-sm text-slate-700 hover:bg-orange-50 hover:text-orange-700">{{ $option }}</button>
        @endforeach
    </div>
</div>
