<?php

namespace Database\Seeders;

use App\Models\DiningArea;
use App\Models\RestaurantFloor;
use App\Models\Table;
use Illuminate\Database\Seeder;

class TableSeeder extends Seeder
{
    public function run(): void
    {
        $floor = RestaurantFloor::firstOrCreate(['name' => 'Planta baja'], [
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $salon = DiningArea::firstOrCreate(
            ['restaurant_floor_id' => $floor->id, 'name' => 'Salón principal'],
            ['type' => 'salon', 'color' => 'orange', 'sort_order' => 1],
        );
        $terrace = DiningArea::firstOrCreate(
            ['restaurant_floor_id' => $floor->id, 'name' => 'Terraza'],
            ['type' => 'terraza', 'color' => 'emerald', 'sort_order' => 2],
        );
        $tables = [
            ['name' => 'Mesa 1', 'capacity' => 2, 'x_pos' => 40, 'y_pos' => 40, 'dining_area_id' => $salon->id, 'shape' => 'round', 'table_width' => 118, 'table_height' => 118, 'orientation' => 'square'],
            ['name' => 'Mesa 2', 'capacity' => 2, 'x_pos' => 280, 'y_pos' => 40, 'dining_area_id' => $salon->id, 'shape' => 'round', 'table_width' => 118, 'table_height' => 118, 'orientation' => 'square'],
            ['name' => 'Mesa 3', 'capacity' => 4, 'x_pos' => 520, 'y_pos' => 40, 'dining_area_id' => $salon->id, 'shape' => 'square', 'table_width' => 118, 'table_height' => 118, 'orientation' => 'square'],
            ['name' => 'Mesa 4', 'capacity' => 4, 'x_pos' => 40, 'y_pos' => 310, 'dining_area_id' => $salon->id, 'shape' => 'square', 'table_width' => 118, 'table_height' => 118, 'orientation' => 'square'],
            ['name' => 'Mesa 5', 'capacity' => 4, 'x_pos' => 280, 'y_pos' => 310, 'dining_area_id' => $salon->id, 'shape' => 'square', 'table_width' => 118, 'table_height' => 118, 'orientation' => 'square'],
            ['name' => 'Mesa 6', 'capacity' => 6, 'x_pos' => 520, 'y_pos' => 310, 'dining_area_id' => $salon->id, 'shape' => 'rectangle', 'table_width' => 238, 'table_height' => 132, 'orientation' => 'horizontal'],
            ['name' => 'Terraza 1', 'capacity' => 2, 'x_pos' => 760, 'y_pos' => 40, 'dining_area_id' => $terrace->id, 'shape' => 'round', 'table_width' => 118, 'table_height' => 118, 'orientation' => 'square'],
            ['name' => 'Terraza 2', 'capacity' => 4, 'x_pos' => 760, 'y_pos' => 310, 'dining_area_id' => $terrace->id, 'shape' => 'square', 'table_width' => 118, 'table_height' => 118, 'orientation' => 'square'],
        ];

        foreach ($tables as $table) {
            Table::updateOrCreate(
                ['name' => $table['name']],
                [...$table, 'restaurant_floor_id' => $floor->id, 'layout_width' => 1, 'layout_height' => 1, 'status' => 'libre'],
            );
        }
    }
}
