<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_table', function (Blueprint $table) {
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('table_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['order_id', 'table_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_table');
    }
};
