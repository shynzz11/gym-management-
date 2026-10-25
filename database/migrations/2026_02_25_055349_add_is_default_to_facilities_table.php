<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('facilities')) {
            return;
        }

        if (! Schema::hasColumn('facilities', 'is_default')) {
            Schema::table('facilities', function (Blueprint $table): void {
                $table->boolean('is_default')->default(false)->after('description');
            });
        }

        $defaultFacilityId = DB::table('facilities')
            ->where('name', 'All Gym Access')
            ->value('id');

        if (! $defaultFacilityId) {
            $defaultFacilityId = DB::table('facilities')
                ->orderBy('id')
                ->value('id');
        }

        if (! $defaultFacilityId) {
            $defaultFacilityId = DB::table('facilities')->insertGetId([
                'name' => 'All Gym Access',
                'description' => null,
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('facilities')->update([
            'is_default' => false,
        ]);

        DB::table('facilities')
            ->where('id', $defaultFacilityId)
            ->update([
                'is_default' => true,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('facilities') || ! Schema::hasColumn('facilities', 'is_default')) {
            return;
        }

        Schema::table('facilities', function (Blueprint $table): void {
            $table->dropColumn('is_default');
        });
    }
};
