<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 50);
            $table->string('address')->nullable();
            $table->string('phone', 30)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'code']);
        });

        Schema::create('company_user', function (Blueprint $table) {
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['company_id', 'user_id']);
        });

        Schema::create('branch_user', function (Blueprint $table) {
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['branch_id', 'user_id']);
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        $setting = DB::table('settings')->orderBy('id')->first();
        $name = trim((string) ($setting->company_name ?? '')) ?: 'Restaurante principal';
        $slug = Str::slug($name) ?: 'restaurante-principal';
        $now = now();
        $companyId = DB::table('companies')->insertGetId([
            'name' => $name,
            'slug' => $slug,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $branchId = DB::table('branches')->insertGetId([
            'company_id' => $companyId,
            'name' => 'Sede principal',
            'code' => 'PRINCIPAL',
            'address' => $setting->company_address ?? null,
            'phone' => $setting->company_phone ?? null,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('settings')->whereNull('company_id')->update(['company_id' => $companyId]);

        $userIds = DB::table('users')
            ->where(fn ($query) => $query->whereNull('type')->orWhere('type', '!=', 'client'))
            ->pluck('id');
        foreach ($userIds as $userId) {
            DB::table('company_user')->insert(['company_id' => $companyId, 'user_id' => $userId]);
            DB::table('branch_user')->insert(['branch_id' => $branchId, 'user_id' => $userId]);
        }
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });

        Schema::dropIfExists('branch_user');
        Schema::dropIfExists('company_user');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('companies');
    }
};
