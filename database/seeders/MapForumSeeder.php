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
        $categories = [
            'camp' => ForumCategory::firstOrCreate(['name' => 'Clan camps'], ['description' => 'The four clan homes on the territory map.', 'sort_order' => 3]),
            'landmark' => ForumCategory::firstOrCreate(['name' => 'Territory landmarks'], ['description' => 'Rivers, forests, paths, and other places to write.', 'sort_order' => 4]),
        ];

        foreach (TerritoryMap::locations() as $index => $location) {
            ForumBoard::firstOrCreate(['slug' => $location['slug']], [
                'forum_category_id' => $categories[$location['category']]->id,
                'name' => $location['name'],
                'description' => 'Role-play at '.$location['name'].'.',
                'is_ic' => true,
                'sort_order' => $index,
            ]);
        }
    }
}