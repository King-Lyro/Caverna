<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $legacy = DB::table('forum_categories')->where('name', 'The living map')->first();
        if (! $legacy) {
            return;
        }

        DB::transaction(function () use ($legacy): void {
            $camps = [
                'thunderclan-territory' => 'ThunderClan Camp',
                'riverclan-territory' => 'RiverClan Camp',
                'shadowclan-territory' => 'ShadowClan Camp',
                'windclan-territory' => 'WindClan Camp',
            ];

            foreach (DB::table('forum_boards')->where('forum_category_id', $legacy->id)->get() as $oldBoard) {
                if (! isset($camps[$oldBoard->slug])) {
                    if (DB::table('forum_threads')->where('forum_board_id', $oldBoard->id)->exists()) {
                        throw new RuntimeException('Move threads from '.$oldBoard->name.' before removing The living map.');
                    }

                    DB::table('forum_boards')->where('id', $oldBoard->id)->delete();

                    continue;
                }

                $campCategoryId = DB::table('forum_categories')->where('name', 'Clan camps')->value('id')
                    ?? DB::table('forum_categories')->insertGetId(['name' => 'Clan camps', 'description' => 'The four clan homes on the territory map.', 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()]);
                $campSlug = str_replace('-territory', '-camp', $oldBoard->slug);
                $campId = DB::table('forum_boards')->where('slug', $campSlug)->value('id')
                    ?? DB::table('forum_boards')->insertGetId(['forum_category_id' => $campCategoryId, 'slug' => $campSlug, 'name' => $camps[$oldBoard->slug], 'description' => 'Role-play at '.$camps[$oldBoard->slug].'.', 'is_ic' => true, 'sort_order' => $oldBoard->sort_order, 'created_at' => now(), 'updated_at' => now()]);

                foreach (DB::table('forum_threads')->where('forum_board_id', $oldBoard->id)->get() as $thread) {
                    $slug = $thread->slug;
                    if (DB::table('forum_threads')->where('forum_board_id', $campId)->where('slug', $slug)->exists()) {
                        $slug = substr($slug, 0, 180).'-migrated-'.$thread->id;
                    }

                    DB::table('forum_threads')->where('id', $thread->id)->update(['forum_board_id' => $campId, 'slug' => $slug]);
                }

                DB::table('forum_boards')->where('id', $oldBoard->id)->delete();
            }

            DB::table('forum_categories')->where('id', $legacy->id)->delete();
        });
    }

    public function down(): void
    {
        // Retired boards are not restored; their threads now belong to the corresponding camp boards.
    }
};