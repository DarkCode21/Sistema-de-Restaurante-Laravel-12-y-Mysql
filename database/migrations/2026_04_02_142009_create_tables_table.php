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
        Schema::create('tables', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Mesa 1, Mesa 2
            $table->integer('capacity')->nullable();
            $table->integer('x_pos')->nullable(); // Posición X en el plano
            $table->integer('y_pos')->nullable(); // Posición Y en el plano
            $table->enum('status', ['libre', 'ocupada', 'reservada'])->default('libre');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tables');
    }
};
