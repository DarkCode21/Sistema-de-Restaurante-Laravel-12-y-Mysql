<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Setting;
use Illuminate\Console\Command;

class ApplyDefaultIgvToProducts extends Command
{
    protected $signature = 'products:apply-default-igv {--apply : Actually perform the update}';

    protected $description = 'Pone settings.default_tax_rate en los productos con tax_rate = 0. Solo modifica productos que aún no tienen tasa asignada explícitamente.';

    public function handle(): int
    {
        $default = (float) (Setting::first()?->default_tax_rate ?? 0);

        if ($default <= 0) {
            $this->error('El IGV por defecto en Settings es 0 o no existe. Configúralo antes de correr este comando.');
            return self::FAILURE;
        }

        $query = Product::where('tax_rate', 0);
        $count = $query->count();

        if ($count === 0) {
            $this->info('No hay productos con tax_rate = 0. Nada que hacer.');
            return self::SUCCESS;
        }

        $this->info("Productos con tax_rate = 0: {$count}");
        $this->info("IGV a aplicar (de Settings): {$default}%");
        $this->newLine();

        $rows = $query->orderBy('name')->get(['id', 'name', 'price', 'tax_rate'])
            ->map(fn (Product $p) => [$p->id, $p->name, $p->price, $p->tax_rate])
            ->all();

        $this->table(['ID', 'Producto', 'Precio', 'Tax actual'], $rows);

        if (!$this->option('apply')) {
            $this->warn('Modo seguro: pasa --apply para ejecutar la actualización.');
            $this->line('Ejemplo: php artisan products:apply-default-igv --apply');
            return self::SUCCESS;
        }

        if (!$this->confirm("¿Actualizar {$count} productos a tax_rate = {$default}%?")) {
            $this->info('Cancelado.');
            return self::SUCCESS;
        }

        $updated = Product::where('tax_rate', 0)->update(['tax_rate' => $default]);
        $this->info("Listo. {$updated} producto(s) actualizados a tax_rate = {$default}%.");
        return self::SUCCESS;
    }
}
