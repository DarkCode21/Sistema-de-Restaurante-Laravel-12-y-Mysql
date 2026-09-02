<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['category' => 'Ceviches', 'name' => 'Ceviche Clásico', 'price' => 25, 'stock' => 35],
            ['category' => 'Ceviches', 'name' => 'Ceviche Mixto', 'price' => 30, 'stock' => 28],
            ['category' => 'Ceviches', 'name' => 'Ceviche de Pescado', 'price' => 22, 'stock' => 32],
            ['category' => 'Tiraditos', 'name' => 'Tiradito Clásico', 'price' => 28, 'stock' => 25],
            ['category' => 'Tiraditos', 'name' => 'Tiradito Ají Amarillo', 'price' => 30, 'stock' => 22],
            ['category' => 'Entradas', 'name' => 'Causa Limeña', 'price' => 18, 'stock' => 30],
            ['category' => 'Entradas', 'name' => 'Yucas Fritas', 'price' => 12, 'stock' => 40],
            ['category' => 'Chicharrones', 'name' => 'Chicharrón de Pescado', 'price' => 20, 'stock' => 26],
            ['category' => 'Chicharrones', 'name' => 'Chicharrón Mixto', 'price' => 24, 'stock' => 24],
            ['category' => 'Arroces', 'name' => 'Arroz con Mariscos', 'price' => 26, 'stock' => 20],
            ['category' => 'Arroces', 'name' => 'Arroz Chaufa de Mariscos', 'price' => 24, 'stock' => 18],
            ['category' => 'Parihuelas', 'name' => 'Parihuela Clásica', 'price' => 32, 'stock' => 14],
            ['category' => 'Bebidas', 'name' => 'Chicha Morada', 'price' => 6, 'stock' => 48, 'requires_kitchen' => false],
            ['category' => 'Bebidas', 'name' => 'Limonada', 'price' => 5, 'stock' => 52, 'requires_kitchen' => false],
            ['category' => 'Bebidas', 'name' => 'Gaseosa Personal', 'price' => 4, 'stock' => 60, 'requires_kitchen' => false],
            ['category' => 'Cervezas', 'name' => 'Cerveza Pilsen', 'price' => 8, 'stock' => 36, 'requires_kitchen' => false],
            ['category' => 'Cervezas', 'name' => 'Cerveza Cusqueña', 'price' => 10, 'stock' => 32, 'requires_kitchen' => false],
            ['category' => 'Postres', 'name' => 'Suspiro Limeño', 'price' => 12, 'stock' => 16],
            ['category' => 'Postres', 'name' => 'Arroz con Leche', 'price' => 10, 'stock' => 18],
        ];

        foreach ($products as $item) {
            $category = Category::where('name', $item['category'])->first();

            if (!$category) {
                continue;
            }

            Product::updateOrCreate(
                ['name' => $item['name']],
                [
                    'category_id' => $category->id,
                    'price' => $item['price'],
                    'stock' => $item['stock'],
                    'status' => true,
                    'image' => 'products/default.png',
                    'requires_kitchen' => $item['requires_kitchen'] ?? true,
                ],
            );
        }
    }
}
