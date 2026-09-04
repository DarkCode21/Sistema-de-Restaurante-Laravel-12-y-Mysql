<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $companyId = DB::table('companies')->where('is_active', true)->value('id');

        if (!$companyId) {
            return;
        }

        DB::table('users')->where('type', 'client')->orderBy('id')->each(function ($customer) use ($companyId): void {
            DB::table('company_user')->insertOrIgnore([
                'company_id' => $companyId,
                'user_id' => $customer->id,
            ]);
        });
    }

    public function down(): void
    {
        DB::table('company_user')->whereIn('user_id', DB::table('users')->where('type', 'client')->select('id'))->delete();
    }
};
