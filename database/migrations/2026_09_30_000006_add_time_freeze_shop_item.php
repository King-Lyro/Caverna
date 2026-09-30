<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('shop_items')->insertOrIgnore([
            'name' => 'Time freeze',
            'slug' => 'time-freeze',
            'description' => 'Pause aging and energy changes until the item is removed from the character.',
            'cost' => 500,
            'effect' => 'Time freeze',
            'is_available' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
    }
};