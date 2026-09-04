<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            $table->decimal('unit_cost', 12, 4)->nullable()->after('minimum_stock');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->decimal('cost', 12, 4)->nullable()->after('price');
        });

        Schema::table('sale_details', function (Blueprint $table) {
            $table->decimal('unit_cost', 12, 4)->nullable()->after('price');
            $table->decimal('cost_total', 12, 2)->nullable()->after('unit_cost');
            $table->decimal('gross_profit', 12, 2)->nullable()->after('cost_total');
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('document_number')->nullable();
            $table->timestamps();
        });

        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference')->nullable();
            $table->decimal('total', 12, 2)->default(0);
            $table->timestamp('purchased_at');
            $table->timestamps();
        });

        Schema::create('purchase_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_cost', 12, 4);
            $table->decimal('total', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_details');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('suppliers');

        Schema::table('sale_details', function (Blueprint $table) {
            $table->dropColumn(['unit_cost', 'cost_total', 'gross_profit']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('cost');
        });

        Schema::table('ingredients', function (Blueprint $table) {
            $table->dropColumn('unit_cost');
        });
    }
};
