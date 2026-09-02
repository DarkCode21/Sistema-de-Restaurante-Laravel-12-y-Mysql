<?php

namespace Database\Seeders;

use App\Models\Table;
use Illuminate\Database\Seeder;

class TableSeeder extends Seeder
{
    public function run(): void
    {
        $tables = [
            ['name' => 'Mesa 1', 'capacity' => 2, 'x_pos' => 40, 'y_pos' => 40],
            ['name' => 'Mesa 2', 'capacity' => 2, 'x_pos' => 280, 'y_pos' => 40],
            ['name' => 'Mesa 3', 'capacity' => 4, 'x_pos' => 520, 'y_pos' => 40],
            ['name' => 'Mesa 4', 'capacity' => 4, 'x_pos' => 40, 'y_pos' => 310],
            ['name' => 'Mesa 5', 'capacity' => 4, 'x_pos' => 280, 'y_pos' => 310],
            ['name' => 'Mesa 6', 'capacity' => 6, 'x_pos' => 520, 'y_pos' => 310],
            ['name' => 'Terraza 1', 'capacity' => 2, 'x_pos' => 760, 'y_pos' => 40],
            ['name' => 'Terraza 2', 'capacity' => 4, 'x_pos' => 760, 'y_pos' => 310],
        ];

        foreach ($tables as $table) {
            Table::updateOrCreate(
                ['name' => $table['name']],
                [...$table, 'status' => 'libre'],
            );
        }
    }
}
