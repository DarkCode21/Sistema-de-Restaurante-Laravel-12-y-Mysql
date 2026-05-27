<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Gastos') }}
        </h2>
    </x-slot>

    @livewire('expense-component')

</x-admin-layout>