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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_register_id')
                  ->constrained('cash_registers')
                  ->onDelete('restrict');
                  
            $table->foreignId('payment_method_id')
                  ->constrained('payment_methods')
                  ->onDelete('restrict');
                  
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('restrict');

            $table->string('concept');
            $table->text('description')->nullable();
            $table->decimal('amount', 10, 2);
            $table->dateTime('expense_date')->useCurrent();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
