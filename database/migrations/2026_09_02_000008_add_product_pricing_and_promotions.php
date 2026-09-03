<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('tax_rate', 5, 2)->default(0)->after('price');
        });

        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('discount_type', 10);
            $table->decimal('value', 10, 2);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index(['product_id', 'active']);
        });

        Schema::table('order_details', function (Blueprint $table) {
            $table->decimal('discount', 10, 2)->default(0)->after('price');
            $table->decimal('tax_rate', 5, 2)->default(0)->after('tax');
        });

        Schema::table('sale_details', function (Blueprint $table) {
            $table->decimal('discount', 10, 2)->default(0)->after('price');
            $table->decimal('tax_rate', 5, 2)->default(0)->after('tax');
        });
    }

    public function down(): void
    {
        Schema::table('sale_details', function (Blueprint $table) {
            $table->dropColumn(['discount', 'tax_rate']);
        });

        Schema::table('order_details', function (Blueprint $table) {
            $table->dropColumn(['discount', 'tax_rate']);
        });

        Schema::dropIfExists('promotions');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('tax_rate');
        });
    }
};
