<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tip_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cash_register_id')->constrained()->restrictOnDelete();
            $table->foreignId('payment_method_id')->constrained()->restrictOnDelete();
            $table->foreignId('adjusted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('reason');
            $table->string('reference')->nullable();
            $table->timestamp('adjusted_at')->useCurrent();
            $table->timestamps();

            $table->index(['branch_id', 'sale_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tip_adjustments');
    }
};
