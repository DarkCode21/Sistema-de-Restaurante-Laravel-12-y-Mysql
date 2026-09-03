<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_details', function (Blueprint $table) {
            $table->foreignId('promotion_id')->nullable()->after('tax_rate')->constrained('promotions')->nullOnDelete();
        });

        Schema::table('sale_details', function (Blueprint $table) {
            $table->foreignId('promotion_id')->nullable()->after('tax_rate')->constrained('promotions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sale_details', function (Blueprint $table) {
            $table->dropConstrainedForeignId('promotion_id');
        });

        Schema::table('order_details', function (Blueprint $table) {
            $table->dropConstrainedForeignId('promotion_id');
        });
    }
};
