<?php

namespace App\Console\Commands;

use Database\Seeders\ParrilladaMenuSeeder;
use Illuminate\Console\Command;

class SeedParrilladaMenu extends Command
{
    protected $signature = 'menu:seed {--fresh : Disable products that are not in the parrillada menu before seeding}';

    protected $description = 'Sembra el menú estándar de la parrillería. Idempotente: usa updateOrCreate por nombre.';

    public function handle(): int
    {
        if ($this->option('fresh')) {
            $menuNames = array_column(ParrilladaMenuSeeder::MENU, 1);
            $parrilladaCategories = ParrilladaMenuSeeder::CATEGORIES;

            $affected = \App\Models\Product::whereNotIn('name', $menuNames)
                ->where('status', true)
                ->update(['status' => false]);

            $staleCategories = \App\Models\Category::whereNotIn('name', $parrilladaCategories)->count();
            if ($staleCategories > 0) {
                if ($this->confirm("Hay {$staleCategories} categorías que no son del menú parrillero. ¿Eliminarlas?")) {
                    \App\Models\Category::whereNotIn('name', $parrilladaCategories)->delete();
                    $this->info("Categorías eliminadas: {$staleCategories}");
                }
            }

            $this->info("Productos desactivados (no están en el menú parrillero): {$affected}");
        }

        $this->call('db:seed', ['--class' => ParrilladaMenuSeeder::class, '--force' => true]);

        $this->info('Menú de parrillería sembrado/actualizado.');
        return self::SUCCESS;
    }
}
