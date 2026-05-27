<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Cajas') }}
        </h2>
    </x-slot>

    @livewire('cash-register-component')

</x-admin-layout>