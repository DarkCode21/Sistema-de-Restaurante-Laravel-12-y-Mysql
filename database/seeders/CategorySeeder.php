<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Ceviches'],
            ['name' => 'Tiraditos'],
            ['name' => 'Entradas'],
            ['name' => 'Chicharrones'],
            ['name' => 'Arroces'],
            ['name' => 'Parihuelas'],
            ['name' => 'Platos criollos'],
            ['name' => 'Bebidas'],
            ['name' => 'Cervezas'],
            ['name' => 'Postres'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}