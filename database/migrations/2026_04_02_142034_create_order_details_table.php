<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            $table->integer('quantity');
            $table->boolean('requires_kitchen')->default(true);
            $table->decimal('price', 10, 2);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('subtotal', 10, 2);
            $table->text('notes')->nullable();
            $table->enum('cooking_status', ['pending', 'in_progress', 'ready', 'served', 'cancelled'])
                ->default('pending');

            $table->boolean('is_printed')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_details');
    }
};
