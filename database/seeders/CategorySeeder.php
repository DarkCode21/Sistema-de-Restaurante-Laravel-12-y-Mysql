<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Parrillas de Pollo'],
            ['name' => 'Parrillas de Carne'],
            ['name' => 'Parrillas de Cerdo'],
            ['name' => 'Guarniciones'],
            ['name' => 'Ensaladas'],
            ['name' => 'Bebidas Sin Alcohol'],
            ['name' => 'Cervezas'],
            ['name' => 'Salsas y Extras'],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(['name' => $category['name']]);
        }
    }
}
