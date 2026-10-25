<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('business_settings')) {
            return;
        }

        $defaults = [
            'currency_symbol' => '₹',
            'timezone' => 'Asia/Kolkata',
            'country' => 'India',
        ];

        foreach ($defaults as $key => $value) {
            $exists = DB::table('business_settings')
                ->where('key', $key)
                ->exists();

            if (! $exists) {
                DB::table('business_settings')->insert([
                    'key' => $key,
                    'value' => $value,
                    'type' => 'string',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('business_settings')) {
            return;
        }

        DB::table('business_settings')
            ->where('key', 'currency_symbol')
            ->where('value', '₹')
            ->delete();

        DB::table('business_settings')
            ->where('key', 'timezone')
            ->where('value', 'Asia/Kolkata')
            ->delete();

        DB::table('business_settings')
            ->where('key', 'country')
            ->where('value', 'India')
            ->delete();
    }
};
