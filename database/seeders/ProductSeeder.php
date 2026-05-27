<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [

            // CEVICHES
            ['category' => 'Ceviches', 'name' => 'Ceviche Clásico', 'price' => 25],
            ['category' => 'Ceviches', 'name' => 'Ceviche Mixto', 'price' => 30],
            ['category' => 'Ceviches', 'name' => 'Ceviche de Pescado', 'price' => 22],

            // TIRADITOS
            ['category' => 'Tiraditos', 'name' => 'Tiradito Clásico', 'price' => 28],
            ['category' => 'Tiraditos', 'name' => 'Tiradito Ají Amarillo', 'price' => 30],

            // ENTRADAS
            ['category' => 'Entradas', 'name' => 'Causa Limeña', 'price' => 18],
            ['category' => 'Entradas', 'name' => 'Yucas Fritas', 'price' => 12],

            // CHICHARRONES
            ['category' => 'Chicharrones', 'name' => 'Chicharrón de Pescado', 'price' => 20],
            ['category' => 'Chicharrones', 'name' => 'Chicharrón Mixto', 'price' => 24],

            // ARROCES
            ['category' => 'Arroces', 'name' => 'Arroz con Mariscos', 'price' => 26],
            ['category' => 'Arroces', 'name' => 'Arroz Chaufa de Mariscos', 'price' => 24],

            // PARIHUELAS
            ['category' => 'Parihuelas', 'name' => 'Parihuela Clásica', 'price' => 32],

            // BEBIDAS
            ['category' => 'Bebidas', 'name' => 'Chicha Morada', 'price' => 6],
            ['category' => 'Bebidas', 'name' => 'Limonada', 'price' => 5],
            ['category' => 'Bebidas', 'name' => 'Gaseosa Personal', 'price' => 4],

            // CERVEZAS
            ['category' => 'Cervezas', 'name' => 'Cerveza Pilsen', 'price' => 8],
            ['category' => 'Cervezas', 'name' => 'Cerveza Cusqueña', 'price' => 10],

            // POSTRES
            ['category' => 'Postres', 'name' => 'Suspiro Limeño', 'price' => 12],
            ['category' => 'Postres', 'name' => 'Arroz con Leche', 'price' => 10],
        ];

        foreach ($products as $item) {
            $category = Category::where('name', $item['category'])->first();

            if (!$category) continue;

            Product::create([
                'category_id' => $category->id,
                'name' => $item['name'],
                'price' => $item['price'],
                'stock' => null,
                'status' => 1,
                'image' => 'products/default.png', // puedes cambiar luego
            ]);
        }
    }
}