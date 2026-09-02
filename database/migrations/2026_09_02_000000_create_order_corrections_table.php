<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_detail_id')->nullable()->constrained()->nullOnDelete();
            $table->string('table_name')->nullable();
            $table->string('product_name');
            $table->unsignedInteger('quantity');
            $table->string('action', 20);
            $table->text('notes')->nullable();
            $table->boolean('requires_kitchen')->default(true);
            $table->timestamp('printed_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();

            $table->index(['requires_kitchen', 'acknowledged_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_corrections');
    }
};
