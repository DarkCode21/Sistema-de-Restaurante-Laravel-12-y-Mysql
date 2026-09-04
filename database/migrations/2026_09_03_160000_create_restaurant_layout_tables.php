<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_floors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedSmallInteger('sort_order')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('dining_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_floor_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type')->default('salon');
            $table->string('color')->default('slate');
            $table->unsignedSmallInteger('sort_order')->default(1);
            $table->timestamps();

            $table->unique(['restaurant_floor_id', 'name']);
        });

        Schema::table('tables', function (Blueprint $table) {
            $table->foreignId('restaurant_floor_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('dining_area_id')->nullable()->after('restaurant_floor_id')->constrained()->nullOnDelete();
            $table->string('shape')->default('square')->after('capacity');
            $table->unsignedTinyInteger('layout_width')->default(1)->after('shape');
            $table->unsignedTinyInteger('layout_height')->default(1)->after('layout_width');
        });

        $now = now();
        $floorId = DB::table('restaurant_floors')->insertGetId([
            'name' => 'Planta baja',
            'sort_order' => 1,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $salonId = DB::table('dining_areas')->insertGetId([
            'restaurant_floor_id' => $floorId,
            'name' => 'Salón principal',
            'type' => 'salon',
            'color' => 'orange',
            'sort_order' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $terraceId = DB::table('dining_areas')->insertGetId([
            'restaurant_floor_id' => $floorId,
            'name' => 'Terraza',
            'type' => 'terraza',
            'color' => 'emerald',
            'sort_order' => 2,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('tables')->update([
            'restaurant_floor_id' => $floorId,
            'dining_area_id' => $salonId,
        ]);
        DB::table('tables')->where('name', 'like', 'Terraza%')->update(['dining_area_id' => $terraceId]);
    }

    public function down(): void
    {
        Schema::table('tables', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dining_area_id');
            $table->dropConstrainedForeignId('restaurant_floor_id');
            $table->dropColumn(['shape', 'layout_width', 'layout_height']);
        });

        Schema::dropIfExists('dining_areas');
        Schema::dropIfExists('restaurant_floors');
    }
};
