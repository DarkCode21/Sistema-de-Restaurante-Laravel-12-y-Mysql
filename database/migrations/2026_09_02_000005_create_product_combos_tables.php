<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_combo')->default(false)->after('requires_kitchen');
        });

        Schema::create('product_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('combo_product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('component_product_id')->constrained('products')->restrictOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();
            $table->unique(['combo_product_id', 'component_product_id']);
        });

        Schema::table('order_details', function (Blueprint $table) {
            $table->foreignId('parent_detail_id')->nullable()->after('order_id')->constrained('order_details')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_details', function (Blueprint $table) {
            $table->dropForeign(['parent_detail_id']);
            $table->dropColumn('parent_detail_id');
        });

        Schema::dropIfExists('product_components');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('is_combo');
        });
    }
};
