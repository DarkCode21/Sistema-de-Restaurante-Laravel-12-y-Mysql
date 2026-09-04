<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('roles', 'company_id')) {
            return;
        }

        $companyId = DB::table('companies')->where('is_active', true)->value('id');

        Schema::table('roles', function (Blueprint $table): void {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });
        Schema::table('model_has_roles', function (Blueprint $table): void {
            $table->foreignId('company_id')->nullable()->after('model_id')->constrained()->nullOnDelete();
        });
        Schema::table('model_has_permissions', function (Blueprint $table): void {
            $table->foreignId('company_id')->nullable()->after('model_id')->constrained()->nullOnDelete();
        });

        DB::table('roles')->whereNull('company_id')->update(['company_id' => $companyId]);
        DB::table('model_has_roles')->whereNull('company_id')->update(['company_id' => $companyId]);
        DB::table('model_has_permissions')->whereNull('company_id')->update(['company_id' => $companyId]);

        Schema::table('roles', function (Blueprint $table): void {
            $table->dropUnique(['name', 'guard_name']);
            $table->unique(['company_id', 'name', 'guard_name']);
        });
    }

    public function down(): void
    {
        // The Spatie migration also creates these columns when teams are enabled.
        // Keeping them makes rollback safe for databases created with that setting.
    }
};
