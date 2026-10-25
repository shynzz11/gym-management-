<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('facilities')) {
            return;
        }

        $defaultFacilities = [
            'All Gym Access',
            'Locker Room',
            'Shower Room',
            'Group Classes',
        ];

        foreach ($defaultFacilities as $facilityName) {
            $exists = DB::table('facilities')
                ->where('name', $facilityName)
                ->exists();

            if (! $exists) {
                DB::table('facilities')->insert([
                    'name' => $facilityName,
                    'description' => null,
                    'is_default' => $facilityName === 'All Gym Access',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('facilities')) {
            return;
        }

        DB::table('facilities')
            ->whereIn('name', [
                'All Gym Access',
                'Locker Room',
                'Shower Room',
                'Group Classes',
            ])
            ->whereNull('description')
            ->delete();
    }
};
