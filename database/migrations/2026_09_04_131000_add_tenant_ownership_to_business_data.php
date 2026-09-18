<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $companyTables = ['categories', 'products', 'ingredients', 'suppliers', 'payment_methods', 'promotions'];

    private array $branchTables = [
        'restaurant_floors',
        'dining_areas',
        'tables',
        'preparation_stations',
        'cash_terminals',
        'cash_registers',
        'orders',
        'sales',
        'expenses',
        'purchases',
    ];

    public function up(): void
    {
        $companyId = DB::table('companies')->where('is_active', true)->value('id');
        $branchId = DB::table('branches')->where('is_active', true)->value('id');

        foreach ($this->companyTables as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->foreignId('company_id')->nullable()->after('id')->constrained()->nullOnDelete();
            });
            DB::table($tableName)->update(['company_id' => $companyId]);
        }

        foreach ($this->branchTables as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->foreignId('branch_id')->nullable()->after('id')->constrained()->nullOnDelete();
            });
            DB::table($tableName)->update(['branch_id' => $branchId]);
        }

        Schema::table('preparation_stations', function (Blueprint $table): void {
            $table->dropUnique('preparation_stations_name_unique');
            $table->unique(['branch_id', 'name']);
        });

        Schema::table('cash_terminals', function (Blueprint $table): void {
            $table->dropUnique('cash_terminals_name_unique');
            $table->unique(['branch_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::table('preparation_stations', function (Blueprint $table): void {
            $table->dropUnique(['branch_id', 'name']);
            $table->unique('name');
        });

        Schema::table('cash_terminals', function (Blueprint $table): void {
            $table->dropUnique(['branch_id', 'name']);
            $table->unique('name');
        });

        foreach ($this->branchTables as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropConstrainedForeignId('branch_id');
            });
        }

        foreach ($this->companyTables as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropConstrainedForeignId('company_id');
            });
        }
    }
};
