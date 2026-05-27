<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Nueva orden') }} - {{ $table->name }}
        </h2>
    </x-slot>

    @livewire('order-create-component', ['table' => $table])

</x-admin-layout>