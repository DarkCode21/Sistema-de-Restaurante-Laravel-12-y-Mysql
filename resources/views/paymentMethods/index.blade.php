<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Métodos de Pago') }}
        </h2>
    </x-slot>

    @livewire('payment-method-component')

</x-admin-layout>
