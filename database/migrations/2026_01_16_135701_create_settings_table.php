<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();

            $table->string('company_name')->default('HelpDesk');
            $table->string('company_email')->nullable();
            $table->string('company_phone')->nullable();
            $table->text('company_address')->nullable();
            $table->string('tax_id')->nullable();
            $table->string('currency_simbol')->default('S/');

            $table->string('logo_path')->nullable();
            $table->string('favicon_path')->nullable();

            $table->string('timezone')->default('UTC');

            $table->json('social_networks')->nullable();

            $table->boolean('direct_printing')->default(false);
            $table->boolean('separate_orders')->default(false);
            $table->string('printer_name')->nullable()->comment('Impresora de Caja / Barra / Bebidas');
            $table->string('kitchen_printer_name')->nullable()->comment('Impresora de Cocina');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
