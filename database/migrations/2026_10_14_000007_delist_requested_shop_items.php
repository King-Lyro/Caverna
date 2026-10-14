<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('shop_items')
            ->whereIn('slug', ['illness-treatment', 'moonlit-eyes-trait'])
            ->update(['is_available' => false]);
    }

    public function down(): void
    {
        DB::table('shop_items')
            ->whereIn('slug', ['illness-treatment', 'moonlit-eyes-trait'])
            ->update(['is_available' => true]);
    }
};
