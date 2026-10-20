<?php

namespace Database\Seeders;

use App\Models\ForumBoard;
use App\Models\ForumCategory;
use App\Support\TerritoryMap;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MapForumSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            foreach (TerritoryMap::forums() as $territory => $names) {
                $category = ForumCategory::updateOrCreate(
                    ['name' => $territory === 'outsider' ? 'Outsider Land' : $territory.' Territory'],
                    ['description' => 'Role-play locations in '.($territory === 'outsider' ? 'outsider land' : $territory).'.', 'sort_order' => 3 + array_search($territory, array_keys(TerritoryMap::forums()), true), 'territory' => $territory],
                );
                foreach ($names as $index => $name) {
                    $slug = TerritoryMap::forumSlug($territory, $name);
                    ForumBoard::updateOrCreate(['slug' => $slug], [
                        'forum_category_id' => $category->id, 'name' => $name,
                        'description' => $name === 'Twoleg Path' ? 'Role-play on the Twoleg path that runs through '.($territory === 'outsider' ? 'outsider land' : $territory.' land').'.'
                            : ($name === 'Twoleg Dens' ? 'Roleplay in one of the various Twoleg cottages that are scattered along the lakeshore.' : 'Role-play at '.$name.'.'),
                        'is_ic' => true, 'sort_order' => $index,
                    ]);
                }
            }
            $outsider = ForumCategory::where('name', 'Outsider Land')->firstOrFail();
            foreach (ForumCategory::whereIn('name', ['outsider Territory', 'Outsider Territory'])->get() as $legacy) {
                foreach ($legacy->boards as $board) {
                    $destination = $outsider->boards()->where('name', $board->name)->first();
                    if ($destination) {
                        $board->threads()->update(['forum_board_id' => $destination->id]);
                        $board->delete();
                    } else {
                        $board->update(['forum_category_id' => $outsider->id]);
                    }
                }
                $legacy->delete();
            }
        });
    }
}
