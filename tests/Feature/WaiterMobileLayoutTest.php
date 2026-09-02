<?php

use App\Livewire\OrderCreateComponent;
use App\Models\Setting;
use App\Models\Table;
use Livewire\Livewire;

it('renders a compact waiter menu for mobile and landscape screens', function () {
    Setting::create(['company_name' => 'Asador de prueba']);
    $table = Table::create([
        'name' => 'Mesa móvil',
        'capacity' => 2,
        'x_pos' => 0,
        'y_pos' => 0,
        'status' => 'libre',
    ]);

    Livewire::test(OrderCreateComponent::class, ['table' => $table])
        ->assertSeeHtml('grid-cols-2 sm:grid-cols-3')
        ->assertSeeHtml('flex-nowrap overflow-x-auto touch-pan-x')
        ->assertSeeHtml('data-fullscreen-toggle');
});

it('implements the waiter full screen control', function () {
    Setting::create(['company_name' => 'Asador de prueba']);
    $table = Table::create([
        'name' => 'Mesa pantalla completa',
        'capacity' => 2,
        'x_pos' => 0,
        'y_pos' => 0,
        'status' => 'libre',
    ]);

    Livewire::test(OrderCreateComponent::class, ['table' => $table])
        ->assertSeeHtml('function toggleRestaurantFullscreen()')
        ->assertSeeHtml('document.documentElement.requestFullscreen');
});
