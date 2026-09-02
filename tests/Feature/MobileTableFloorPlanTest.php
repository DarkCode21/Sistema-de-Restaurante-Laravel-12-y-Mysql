<?php

use App\Livewire\TableComponent;
use App\Models\Table;
use Livewire\Livewire;

it('renders the complete floor plan inside a touch-scrollable mobile viewport', function () {
    Table::create([
        'name' => 'Mesa derecha',
        'capacity' => 4,
        'x_pos' => 900,
        'y_pos' => 100,
        'status' => 'libre',
    ]);

    Livewire::test(TableComponent::class)
        ->assertSeeHtml('data-floor-scroll')
        ->assertSeeHtml('overflow-x-auto touch-pan-x')
        ->assertSeeHtml('min-w-[1200px]')
        ->assertSeeHtml('data-fullscreen-toggle');
});
