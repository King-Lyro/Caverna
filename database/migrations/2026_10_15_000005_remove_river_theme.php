<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('site_settings')->where('key', 'theme')->where('value', 'river')
            ->update(['value' => 'fall', 'updated_at' => now()]);
        Cache::forget('site_theme');
    }

    public function down(): void
    {
        // Preserve the selected seasonal theme.
    }
};
