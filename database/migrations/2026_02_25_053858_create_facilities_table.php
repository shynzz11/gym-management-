<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('facilities', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description', 500)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        $defaultFacilities = [
            'All Gym Access',
            'Locker Room',
            'Shower Room',
            'Group Classes',
        ];

        $seedFacilities = collect($defaultFacilities);

        if (Schema::hasTable('plans') && Schema::hasColumn('plans', 'facilities')) {
            DB::table('plans')
                ->select('facilities')
                ->whereNotNull('facilities')
                ->get()
                ->pluck('facilities')
                ->each(function (mixed $facilities) use ($seedFacilities): void {
                    $decodedFacilities = is_string($facilities) ? json_decode($facilities, true) : $facilities;

                    if (! is_array($decodedFacilities)) {
                        return;
                    }

                    foreach ($decodedFacilities as $facilityName) {
                        if (is_string($facilityName)) {
                            $seedFacilities->push($facilityName);
                        }
                    }
                });
        }

        $now = now();
        $rows = $seedFacilities
            ->map(fn (string $facilityName) => trim($facilityName))
            ->filter(fn (string $facilityName) => $facilityName !== '')
            ->unique(fn (string $facilityName) => Str::lower($facilityName))
            ->map(fn (string $facilityName) => [
                'name' => $facilityName,
                'description' => null,
                'is_default' => Str::lower($facilityName) === 'all gym access',
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->values()
            ->all();

        if ($rows !== []) {
            DB::table('facilities')->insert($rows);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facilities');
    }
};
