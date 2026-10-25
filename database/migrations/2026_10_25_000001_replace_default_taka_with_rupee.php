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

        DB::table('business_settings')
            ->where('key', 'currency_symbol')
            ->where('value', '৳')
            ->update([
                'value' => '₹',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Currency changes made after this migration cannot be distinguished from the migrated default.
    }
};
