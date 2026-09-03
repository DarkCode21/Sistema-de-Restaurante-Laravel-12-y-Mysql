<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('manual_discount', 10, 2)->default(0)->after('tax');
            $table->string('manual_discount_reason')->nullable()->after('manual_discount');
            $table->foreignId('manual_discount_by')->nullable()->after('manual_discount_reason')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('manual_discount_by');
            $table->dropColumn(['manual_discount', 'manual_discount_reason']);
        });
    }
};
