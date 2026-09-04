<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_terminals', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('cash_registers', function (Blueprint $table) {
            $table->foreignId('cash_terminal_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->decimal('closing_amount', 15, 2)->nullable()->after('current_amount');
            $table->decimal('difference', 15, 2)->nullable()->after('closing_amount');
            $table->text('closing_notes')->nullable()->after('notes');
        });

        $terminalIds = [];
        foreach (DB::table('cash_registers')->select('name')->distinct()->pluck('name') as $name) {
            $terminalIds[$name] = DB::table('cash_terminals')->insertGetId([
                'name' => $name,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach ($terminalIds as $name => $terminalId) {
            DB::table('cash_registers')->where('name', $name)->update(['cash_terminal_id' => $terminalId]);
        }
    }

    public function down(): void
    {
        Schema::table('cash_registers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cash_terminal_id');
            $table->dropColumn(['closing_amount', 'difference', 'closing_notes']);
        });

        Schema::dropIfExists('cash_terminals');
    }
};
