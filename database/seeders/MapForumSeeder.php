<?php

namespace Database\Seeders;

use App\Models\ForumBoard;
use App\Models\ForumCategory;
use App\Support\TerritoryMap;
use Illuminate\Database\Seeder;

class MapForumSeeder extends Seeder
{
    public function run(): void
    {
        foreach (TerritoryMap::locations() as $index => $location) {
            $category = ForumCategory::firstOrCreate(
                ['name' => $location['territory'].' Territory'],
                ['description' => 'Role-play locations in '.$location['territory'].'.', 'sort_order' => 3, 'territory' => $location['territory']],
            );
            $board = ForumBoard::firstOrCreate(['slug' => $location['slug']], [
                'forum_category_id' => $category->id,
                'name' => $location['name'],
                'description' => 'Role-play at '.$location['name'].'.',
                'is_ic' => true,
                'sort_order' => $index,
            ]);
            if ($board->forum_category_id !== $category->id) {
                $board->update(['forum_category_id' => $category->id]);
            }
        }
    }
}