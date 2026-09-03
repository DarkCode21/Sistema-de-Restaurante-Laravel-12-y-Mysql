<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->where('default_tax_rate', 0)->update(['default_tax_rate' => 18]);
    }

    public function down(): void
    {
    }
};
