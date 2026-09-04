<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tables', function (Blueprint $table) {
            $table->unsignedSmallInteger('table_width')->default(118)->after('layout_height');
            $table->unsignedSmallInteger('table_height')->default(118)->after('table_width');
            $table->string('orientation')->default('square')->after('table_height');
        });

        DB::table('tables')->where('layout_width', '>', 1)->update([
            'table_width' => 250,
            'orientation' => 'horizontal',
        ]);
        DB::table('tables')->where('layout_height', '>', 1)->update([
            'table_height' => 280,
            'orientation' => 'vertical',
        ]);
    }

    public function down(): void
    {
        Schema::table('tables', function (Blueprint $table) {
            $table->dropColumn(['table_width', 'table_height', 'orientation']);
        });
    }
};
