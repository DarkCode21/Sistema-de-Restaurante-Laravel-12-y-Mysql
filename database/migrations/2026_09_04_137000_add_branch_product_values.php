<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branch_product_stocks', function (Blueprint $table): void {
            $table->decimal('price', 12, 2)->nullable()->after('stock');
            $table->decimal('cost', 12, 4)->nullable()->after('price');
            $table->boolean('is_available')->default(true)->after('cost');
        });

        DB::table('branch_product_stocks')->orderBy('id')->each(function (object $branchStock): void {
            $product = DB::table('products')->where('id', $branchStock->product_id)->first(['price', 'cost', 'status']);

            if ($product) {
                DB::table('branch_product_stocks')->where('id', $branchStock->id)->update([
                    'price' => $product->price,
                    'cost' => $product->cost,
                    'is_available' => $product->status,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('branch_product_stocks', function (Blueprint $table): void {
            $table->dropColumn(['price', 'cost', 'is_available']);
        });
    }
};
