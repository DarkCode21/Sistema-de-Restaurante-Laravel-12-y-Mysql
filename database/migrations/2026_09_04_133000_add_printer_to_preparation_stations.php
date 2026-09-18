<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('preparation_stations', function (Blueprint $table) {
            $table->string('printer_name')->nullable()->after('name');
        });

        $printerName = DB::table('settings')->whereNotNull('kitchen_printer_name')->value('kitchen_printer_name');
        if ($printerName) {
            DB::table('preparation_stations')->update(['printer_name' => $printerName]);
        }
    }

    public function down(): void
    {
        Schema::table('preparation_stations', function (Blueprint $table) {
            $table->dropColumn('printer_name');
        });
    }
};
