<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_edits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('edited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('previous_payment_method_id')->constrained('payment_methods')->restrictOnDelete();
            $table->foreignId('new_payment_method_id')->constrained('payment_methods')->restrictOnDelete();
            $table->decimal('previous_amount', 10, 2);
            $table->decimal('new_amount', 10, 2);
            $table->string('previous_reference')->nullable();
            $table->string('new_reference')->nullable();
            $table->string('reason');
            $table->timestamps();
            $table->index(['branch_id', 'sale_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_edits');
    }
};
