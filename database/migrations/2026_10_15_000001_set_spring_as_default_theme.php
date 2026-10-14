<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('site_settings')
            ->where('key', 'theme')
            ->where('value', 'fall')
            ->update(['value' => 'spring', 'updated_at' => now()]);
        Cache::forget('site_theme');
    }

    public function down(): void
    {
        DB::table('site_settings')
            ->where('key', 'theme')
            ->where('value', 'spring')
            ->update(['value' => 'fall', 'updated_at' => now()]);
        Cache::forget('site_theme');
    }
};
