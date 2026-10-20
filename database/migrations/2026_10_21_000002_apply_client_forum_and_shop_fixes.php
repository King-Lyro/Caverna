<?php

use App\Support\TerritoryMap;
use Database\Seeders\MapForumSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            if (DB::table('forum_boards')->whereIn('slug', array_column(TerritoryMap::locations(), 'slug'))->exists()) {
                app(MapForumSeeder::class)->run();
            }
            DB::table('shop_items')->where('slug', 'moss-poultice')->update(['is_available' => false]);
        });
    }

    public function down(): void
    {
        // Content corrections are retained to avoid losing posts or restoring retired items.
    }
};
