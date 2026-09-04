<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_product_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('stock', 12, 3)->default(0);
            $table->timestamps();
            $table->unique(['branch_id', 'product_id']);
        });

        Schema::create('branch_ingredient_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            $table->decimal('stock', 12, 3)->default(0);
            $table->decimal('minimum_stock', 12, 3)->default(0);
            $table->decimal('unit_cost', 12, 4)->nullable();
            $table->timestamps();
            $table->unique(['branch_id', 'ingredient_id']);
        });

        $branchId = DB::table('branches')->where('is_active', true)->value('id');
        $now = now();

        DB::table('products')->orderBy('id')->each(function (object $product) use ($branchId, $now): void {
            DB::table('branch_product_stocks')->insert([
                'branch_id' => $branchId,
                'product_id' => $product->id,
                'stock' => $product->stock ?? 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });

        DB::table('ingredients')->orderBy('id')->each(function (object $ingredient) use ($branchId, $now): void {
            DB::table('branch_ingredient_stocks')->insert([
                'branch_id' => $branchId,
                'ingredient_id' => $ingredient->id,
                'stock' => $ingredient->stock ?? 0,
                'minimum_stock' => $ingredient->minimum_stock ?? 0,
                'unit_cost' => $ingredient->unit_cost,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_ingredient_stocks');
        Schema::dropIfExists('branch_product_stocks');
    }
};
