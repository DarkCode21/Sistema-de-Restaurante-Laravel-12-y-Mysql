<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('preparation_station_id')->nullable()->after('category_id')->constrained()->nullOnDelete();
        });

        Schema::table('order_details', function (Blueprint $table) {
            $table->foreignId('preparation_station_id')->nullable()->after('product_id')->constrained()->nullOnDelete();
            $table->json('selected_options')->nullable()->after('notes');
        });

        Schema::table('order_corrections', function (Blueprint $table) {
            $table->foreignId('preparation_station_id')->nullable()->after('order_detail_id')->constrained()->nullOnDelete();
            $table->json('selected_options')->nullable()->after('notes');
        });

        Schema::table('sale_details', function (Blueprint $table) {
            $table->json('selected_options')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('sale_details', function (Blueprint $table) {
            $table->dropColumn('selected_options');
        });

        Schema::table('order_corrections', function (Blueprint $table) {
            $table->dropForeign(['preparation_station_id']);
            $table->dropColumn(['preparation_station_id', 'selected_options']);
        });

        Schema::table('order_details', function (Blueprint $table) {
            $table->dropForeign(['preparation_station_id']);
            $table->dropColumn(['preparation_station_id', 'selected_options']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['preparation_station_id']);
            $table->dropColumn('preparation_station_id');
        });
    }
};
