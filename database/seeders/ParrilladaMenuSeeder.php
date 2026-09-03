<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class ParrilladaMenuSeeder extends Seeder
{
    public const MENU = [
        ['Parrillas de Pollo',   '¼ Pollo a la Parrilla',            16.00,  60, 'products/grill-chicken.png', true],
        ['Parrillas de Pollo',   'Parrilla de Pollo - Pecho',         24.00,  45, 'products/grill-chicken.png', true],
        ['Parrillas de Pollo',   'Parrilla de Pollo - Pierna',        22.00,  48, 'products/grill-chicken.png', true],
        ['Parrillas de Pollo',   'Pollo Entero a la Parrilla',        58.00,  20, 'products/grill-chicken.png', true],
        ['Parrillas de Pollo',   'Alitas a la Parrilla (6 und)',      26.00,  35, 'products/grill-chicken.png', true],

        ['Parrillas de Carne',   'Parrilla de Carne Mixta',           38.00,  30, 'products/grill-beef.png',    true],
        ['Parrillas de Carne',   'Bife Ancho a la Parrilla',          46.00,  20, 'products/grill-beef.png',    true],
        ['Parrillas de Carne',   'Bife Angosto a la Parrilla',        44.00,  20, 'products/grill-beef.png',    true],
        ['Parrillas de Carne',   'Anticuchos de Corazón (3 und)',     22.00,  40, 'products/grill-beef.png',    true],
        ['Parrillas de Carne',   'Chorizo Parrillero',                14.00,  60, 'products/grill-beef.png',    true],
        ['Parrillas de Carne',   'Mollejas a la Parrilla',            20.00,  30, 'products/grill-beef.png',    true],
        ['Parrillas de Carne',   'Riñón a la Parrilla',               22.00,  25, 'products/grill-beef.png',    true],

        ['Parrillas de Cerdo',   'Parrilla de Cerdo',                 30.00,  35, 'products/grill-pork.png',    true],
        ['Parrillas de Cerdo',   'Costillas de Cerdo BBQ',            34.00,  28, 'products/grill-pork.png',    true],
        ['Parrillas de Cerdo',   'Chuleta de Cerdo',                  28.00,  30, 'products/grill-pork.png',    true],
        ['Parrillas de Cerdo',   'Brochetas de Cerdo',                18.00,  34, 'products/grill-pork.png',    true],

        ['Guarniciones',         'Papas Fritas',                       8.00, 100, 'products/grill-sides.png',   true],
        ['Guarniciones',         'Papas Ancochadas',                   8.00,  90, 'products/grill-sides.png',   true],
        ['Guarniciones',         'Yuca Frita',                         9.00,  60, 'products/grill-sides.png',   true],
        ['Guarniciones',         'Porción de Arroz Blanco',            5.00,  80, 'products/grill-sides.png',   true],
        ['Guarniciones',         'Tacu Tacu',                         12.00,  40, 'products/grill-sides.png',   true],
        ['Guarniciones',         'Choclo a la Parrilla',               7.00,  45, 'products/grill-sides.png',   true],
        ['Guarniciones',         'Plátano a la Parrilla',              6.00,  40, 'products/grill-sides.png',   true],
        ['Guarniciones',         'Camote Glaseado',                    7.00,  40, 'products/grill-sides.png',   true],

        ['Ensaladas',            'Ensalada Mixta',                     8.00,  50, 'products/grill-sides.png',   false],
        ['Ensaladas',            'Ensalada César con Pollo',          16.00,  25, 'products/grill-sides.png',   false],
        ['Ensaladas',            'Ensalada de Palta',                 12.00,  30, 'products/grill-sides.png',   false],
        ['Ensaladas',            'Ensalada Criolla',                   6.00,  55, 'products/grill-sides.png',   false],

        ['Bebidas Sin Alcohol',  'Chicha Morada (Vaso)',               5.00,  90, 'products/grill-drinks.png',  false],
        ['Bebidas Sin Alcohol',  'Chicha Morada (Jarra 1L)',          18.00,  30, 'products/grill-drinks.png',  false],
        ['Bebidas Sin Alcohol',  'Limonada (Vaso)',                    5.00,  80, 'products/grill-drinks.png',  false],
        ['Bebidas Sin Alcohol',  'Limonada Frozen',                   12.00,  40, 'products/grill-drinks.png',  false],
        ['Bebidas Sin Alcohol',  'Maracuyá Frozen',                    9.00,  40, 'products/grill-drinks.png',  false],
        ['Bebidas Sin Alcohol',  'Inca Kola Personal 500ml',           5.00, 120, 'products/grill-drinks.png',  false],
        ['Bebidas Sin Alcohol',  'Coca-Cola Personal 500ml',           5.00, 120, 'products/grill-drinks.png',  false],
        ['Bebidas Sin Alcohol',  'Sprite Personal 500ml',              5.00,  80, 'products/grill-drinks.png',  false],
        ['Bebidas Sin Alcohol',  'Agua Mineral sin gas',               4.00, 100, 'products/grill-drinks.png',  false],
        ['Bebidas Sin Alcohol',  'Agua Mineral con gas',               4.00,  80, 'products/grill-drinks.png',  false],

        ['Cervezas',             'Cerveza Pilsen 650ml',              10.00,  72, 'products/grill-drinks.png',  false],
        ['Cervezas',             'Cerveza Cusqueña 650ml',            12.00,  60, 'products/grill-drinks.png',  false],
        ['Cervezas',             'Cerveza Cristal 650ml',             10.00,  48, 'products/grill-drinks.png',  false],

        ['Salsas y Extras',      'Salsa Chimichurri',                  2.00, 150, 'products/grill-sides.png',   false],
        ['Salsas y Extras',      'Salsa de Ají',                       2.00, 150, 'products/grill-sides.png',   false],
        ['Salsas y Extras',      'Salsa Huancaína',                    3.00, 100, 'products/grill-sides.png',   false],
        ['Salsas y Extras',      'Huevo Frito',                        4.00,  60, 'products/grill-sides.png',   true],
        ['Salsas y Extras',      'Queso Extra',                        3.00, 100, 'products/grill-sides.png',   false],
    ];

    public const CATEGORIES = [
        'Parrillas de Pollo',
        'Parrillas de Carne',
        'Parrillas de Cerdo',
        'Guarniciones',
        'Ensaladas',
        'Bebidas Sin Alcohol',
        'Cervezas',
        'Salsas y Extras',
    ];

    public function run(): void
    {
        $defaultTaxRate = (float) (Setting::first()?->default_tax_rate ?? 18);

        $categories = collect(self::CATEGORIES)->mapWithKeys(function (string $name): array {
            $category = Category::withTrashed()->firstOrNew(['name' => $name]);
            $category->save();
            if ($category->trashed()) {
                $category->restore();
            }
            return [$name => $category];
        });

        foreach (self::MENU as [$categoryName, $name, $price, $stock, $image, $requiresKitchen]) {
            Product::updateOrCreate(
                ['name' => $name],
                [
                    'category_id' => $categories[$categoryName]->id,
                    'price' => $price,
                    'tax_rate' => $defaultTaxRate,
                    'stock' => $stock,
                    'status' => true,
                    'image' => $image,
                    'requires_kitchen' => $requiresKitchen,
                ],
            );
        }
    }
}
