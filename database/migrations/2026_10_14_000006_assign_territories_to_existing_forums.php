<?php

use App\Models\ForumCategory;
use App\Models\ForumBoard;
use App\Support\TerritoryMap;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        foreach (TerritoryMap::locations() as $index => $location) {
            $category = ForumCategory::firstOrCreate(
                ['name' => $location['territory'].' Territory'],
                ['description' => 'Role-play locations in '.$location['territory'].'.', 'sort_order' => 3, 'territory' => $location['territory']],
            );
            $category->update(['territory' => $location['territory']]);
            ForumBoard::query()->where('slug', $location['slug'])->update(['forum_category_id' => $category->id]);
        }
        ForumCategory::query()->whereIn('name', ['Clan camps', 'Territory landmarks'])->doesntHave('boards')->delete();
    }

    public function down(): void
    {
        $camps = ForumCategory::firstOrCreate(
            ['name' => 'Clan camps'],
            ['description' => 'The four Clan camps.', 'sort_order' => 2],
        );
        $landmarks = ForumCategory::firstOrCreate(
            ['name' => 'Territory landmarks'],
            ['description' => 'Locations across the territories.', 'sort_order' => 3],
        );
        foreach (TerritoryMap::locations() as $location) {
            ForumBoard::query()->where('slug', $location['slug'])->update([
                'forum_category_id' => $location['category'] === 'camp' ? $camps->id : $landmarks->id,
            ]);
        }

        ForumCategory::query()->whereIn('territory', ['ThunderClan', 'RiverClan', 'ShadowClan', 'WindClan', 'outsider'])
            ->where('name', 'like', '% Territory')
            ->update(['territory' => null]);
    }
};
