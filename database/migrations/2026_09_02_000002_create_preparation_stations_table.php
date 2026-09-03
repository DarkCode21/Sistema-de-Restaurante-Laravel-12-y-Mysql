<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preparation_stations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('preparation_station_user', function (Blueprint $table) {
            $table->foreignId('preparation_station_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unique(['preparation_station_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preparation_station_user');
        Schema::dropIfExists('preparation_stations');
    }
};
