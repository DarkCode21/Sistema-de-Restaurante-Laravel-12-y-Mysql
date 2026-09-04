<?php

use App\Livewire\TableComponent;
use App\Models\DiningArea;
use App\Models\RestaurantFloor;
use App\Models\Table;
use Livewire\Livewire;

it('keeps physical table positions inside a scrollable mobile canvas', function () {
    $floor = RestaurantFloor::firstOrCreate(['name' => 'Planta baja']);
    $area = DiningArea::firstOrCreate([
        'restaurant_floor_id' => $floor->id,
        'name' => 'Salón principal',
    ], ['type' => 'salon', 'color' => 'orange']);

    Table::create([
        'restaurant_floor_id' => $floor->id,
        'dining_area_id' => $area->id,
        'name' => 'Mesa derecha',
        'capacity' => 4,
        'shape' => 'rectangle',
        'table_width' => 250,
        'table_height' => 130,
        'orientation' => 'horizontal',
        'x_pos' => 900,
        'y_pos' => 100,
        'status' => 'libre',
    ]);

    Livewire::test(TableComponent::class)
        ->assertSeeHtml('data-floor-scroll')
        ->assertSeeHtml('restaurant-floor-canvas')
        ->assertSeeHtml('restaurant-table--canvas')
        ->assertSeeHtml('restaurant-table__body')
        ->assertSeeHtml('restaurant-table__chair')
        ->assertSeeHtml('--restaurant-table-width: 250px')
        ->assertDontSeeHtml('grid-template-columns')
        ->assertSee('Planta baja')
        ->assertSee('Salón principal')
        ->assertSee('Estado en tiempo real');
});
