<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('customer_name')->nullable()->after('order_id');
        });

        Schema::table('sale_details', function (Blueprint $table) {
            $table->string('product_name')->nullable()->after('product_id');
        });
    }

    public function down(): void
    {
        Schema::table('sale_details', function (Blueprint $table) {
            $table->dropColumn('product_name');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('customer_name');
        });
    }
};
