<?php

use App\Livewire\TableComponent;
use App\Livewire\RestaurantLayoutComponent;
use App\Models\DiningArea;
use App\Models\RestaurantFloor;
use App\Models\Table;
use App\Models\User;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

function layoutAdmin(): User
{
    $user = User::factory()->create();
    $permission = Permission::firstOrCreate(['name' => 'mesas.editar']);
    $user->givePermissionTo($permission);
    $user->givePermissionTo(Permission::firstOrCreate(['name' => 'mesas.eliminar']));
    $user->givePermissionTo(Permission::firstOrCreate(['name' => 'empresa.editar']));

    return $user;
}

it('manages floors and zones outside the table editor', function () {
    $screen = Livewire::actingAs(layoutAdmin())
        ->test(RestaurantLayoutComponent::class)
        ->set('floor_name', 'Segundo piso')
        ->call('addFloor');

    $floor = RestaurantFloor::where('name', 'Segundo piso')->firstOrFail();

    $screen->set('restaurant_floor_id', $floor->id)
        ->set('area_name', 'Terraza')
        ->set('area_type', 'terraza')
        ->call('addArea');

    expect(DiningArea::where('restaurant_floor_id', $floor->id)->where('name', 'Terraza')->exists())->toBeTrue();
});

it('deletes a table from the layout editor', function () {
    $table = Table::create([
        'name' => 'Mesa por retirar',
        'capacity' => 4,
        'x_pos' => 0,
        'y_pos' => 0,
        'status' => 'libre',
    ]);

    Livewire::actingAs(layoutAdmin())
        ->test(TableComponent::class)
        ->call('destroy', $table->id);

    expect(Table::find($table->id))->toBeNull();
});

it('assigns a new table to the selected floor and zone in the first free position', function () {
    $floor = RestaurantFloor::create(['name' => 'Segundo piso', 'sort_order' => 2]);
    $area = DiningArea::create([
        'restaurant_floor_id' => $floor->id,
        'name' => 'Privado',
        'type' => 'privado',
        'color' => 'sky',
    ]);
    Table::create([
        'restaurant_floor_id' => $floor->id,
        'dining_area_id' => $area->id,
        'name' => 'Mesa existente',
        'capacity' => 4,
        'shape' => 'square',
        'layout_width' => 1,
        'layout_height' => 1,
        'table_width' => 132,
        'table_height' => 132,
        'orientation' => 'square',
        'x_pos' => 20,
        'y_pos' => 20,
        'status' => 'libre',
    ]);

    Livewire::actingAs(layoutAdmin())
        ->test(TableComponent::class)
        ->call('create')
        ->set('name', 'Mesa nueva')
        ->set('capacity', 6)
        ->set('restaurant_floor_id', $floor->id)
        ->set('dining_area_id', $area->id)
        ->set('shape', 'rectangle')
        ->set('table_width', 250)
        ->set('table_height', 130)
        ->set('orientation', 'horizontal')
        ->call('store');

    $table = Table::where('name', 'Mesa nueva')->firstOrFail();

    expect($table->restaurant_floor_id)->toBe($floor->id)
        ->and($table->dining_area_id)->toBe($area->id)
        ->and($table->shape)->toBe('rectangle')
        ->and($table->table_width)->toBe(250)
        ->and($table->table_height)->toBe(130)
        ->and($table->orientation)->toBe('horizontal')
        ->and([$table->x_pos, $table->y_pos])->not->toBe([20, 20]);
});

it('saves free positions together and rejects overlaps', function () {
    $floor = RestaurantFloor::create(['name' => 'Terraza superior', 'sort_order' => 3]);
    $area = DiningArea::create([
        'restaurant_floor_id' => $floor->id,
        'name' => 'Terraza',
        'type' => 'terraza',
        'color' => 'emerald',
    ]);
    $first = Table::create([
        'restaurant_floor_id' => $floor->id,
        'dining_area_id' => $area->id,
        'name' => 'Mesa uno',
        'capacity' => 4,
        'shape' => 'square',
        'layout_width' => 1,
        'layout_height' => 1,
        'table_width' => 132,
        'table_height' => 132,
        'orientation' => 'square',
        'x_pos' => 20,
        'y_pos' => 20,
        'status' => 'libre',
    ]);
    $second = Table::create([
        'restaurant_floor_id' => $floor->id,
        'dining_area_id' => $area->id,
        'name' => 'Mesa dos',
        'capacity' => 4,
        'shape' => 'square',
        'layout_width' => 1,
        'layout_height' => 1,
        'table_width' => 132,
        'table_height' => 132,
        'orientation' => 'square',
        'x_pos' => 260,
        'y_pos' => 20,
        'status' => 'libre',
    ]);

    Livewire::actingAs(layoutAdmin())
        ->test(TableComponent::class)
        ->set('selectedFloorId', $floor->id)
        ->call('savePositions', [
            ['id' => $first->id, 'x' => 503, 'y' => 299],
            ['id' => $second->id, 'x' => 20, 'y' => 20],
        ]);

    expect($first->refresh()->x_pos)->toBe(503)
        ->and($first->y_pos)->toBe(299)
        ->and($second->refresh()->x_pos)->toBe(20)
        ->and($second->y_pos)->toBe(20);

    Livewire::actingAs(layoutAdmin())
        ->test(TableComponent::class)
        ->set('selectedFloorId', $floor->id)
        ->call('savePositions', [
            ['id' => $first->id, 'x' => 20, 'y' => 20],
            ['id' => $second->id, 'x' => 20, 'y' => 20],
        ])
        ->assertDispatched('swal');

    expect($first->refresh()->x_pos)->toBe(503)
        ->and($first->y_pos)->toBe(299)
        ->and($second->refresh()->x_pos)->toBe(20)
        ->and($second->y_pos)->toBe(20);
});

it('uses proportional presets when selecting a rectangular orientation', function () {
    Livewire::actingAs(layoutAdmin())
        ->test(TableComponent::class)
        ->call('create')
        ->set('shape', 'rectangle')
        ->assertSet('orientation', 'horizontal')
        ->assertSet('table_width', 250)
        ->assertSet('table_height', 130)
        ->set('orientation', 'vertical')
        ->assertSet('table_width', 130)
        ->assertSet('table_height', 280);
});

it('distributes fixed-size chairs symmetrically along the longer table sides', function () {
    $horizontal = Table::make([
        'name' => 'Mesa horizontal',
        'capacity' => 8,
        'status' => 'libre',
        'shape' => 'rectangle',
        'table_width' => 250,
        'table_height' => 130,
        'orientation' => 'horizontal',
    ]);
    $vertical = Table::make([
        'name' => 'Mesa vertical',
        'capacity' => 8,
        'status' => 'libre',
        'shape' => 'rectangle',
        'table_width' => 130,
        'table_height' => 280,
        'orientation' => 'vertical',
    ]);

    $horizontalHtml = Blade::render('<x-restaurant-table :table="$table" preview />', ['table' => $horizontal]);
    $verticalHtml = Blade::render('<x-restaurant-table :table="$table" preview />', ['table' => $vertical]);

    expect(substr_count($horizontalHtml, 'data-side="top"'))->toBe(3)
        ->and(substr_count($horizontalHtml, 'data-side="bottom"'))->toBe(3)
        ->and(substr_count($horizontalHtml, 'data-side="left"'))->toBe(1)
        ->and(substr_count($horizontalHtml, 'data-side="right"'))->toBe(1)
        ->and(substr_count($verticalHtml, 'data-side="left"'))->toBe(3)
        ->and(substr_count($verticalHtml, 'data-side="right"'))->toBe(3)
        ->and(substr_count($verticalHtml, 'data-side="top"'))->toBe(1)
        ->and(substr_count($verticalHtml, 'data-side="bottom"'))->toBe(1);
});
