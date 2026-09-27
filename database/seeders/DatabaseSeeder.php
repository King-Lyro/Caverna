<?php

namespace Database\Seeders;

use App\Models\ForumCategory;
use App\Models\ShopItem;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $world = ForumCategory::create([
            'name' => 'The world',
            'description' => 'Everything you need to find your place in Cavernas.',
            'sort_order' => 1,
        ]);
        $world->boards()->createMany([
            ['name' => 'Announcements', 'slug' => 'announcements', 'description' => 'News, events, and notes from the staff team.', 'sort_order' => 1],
            ['name' => 'Rules & guides', 'slug' => 'rules-guides', 'description' => 'Start here before you step onto the path.', 'sort_order' => 2],
            ['name' => 'Out of character', 'slug' => 'out-of-character', 'description' => 'Introductions, questions, and community conversation.', 'sort_order' => 3],
        ]);

        $story = ForumCategory::create([
            'name' => 'The living map',
            'description' => 'Write the moments that change the territory.',
            'sort_order' => 2,
        ]);
        $story->boards()->createMany([
            ['name' => 'ThunderClan territory', 'slug' => 'thunderclan-territory', 'description' => 'Open role-play in the land of ThunderClan.', 'is_ic' => true, 'sort_order' => 1],
            ['name' => 'RiverClan territory', 'slug' => 'riverclan-territory', 'description' => 'Open role-play along the river and its banks.', 'is_ic' => true, 'sort_order' => 2],
            ['name' => 'ShadowClan territory', 'slug' => 'shadowclan-territory', 'description' => 'Open role-play beneath the shadowed pines.', 'is_ic' => true, 'sort_order' => 3],
            ['name' => 'WindClan territory', 'slug' => 'windclan-territory', 'description' => 'Open role-play across the high, open ground.', 'is_ic' => true, 'sort_order' => 4],
        ]);

        ShopItem::create(['name' => 'Moss poultice', 'slug' => 'moss-poultice', 'description' => 'A small restorative bundle that can help return energy to a weary character.', 'cost' => 100, 'effect' => 'Energy restoration']);
        ShopItem::create(['name' => 'Energy return', 'slug' => 'energy-return', 'description' => 'Restores 10 energy to one character.', 'cost' => 20, 'effect' => 'Energy return']);
        ShopItem::create(['name' => 'Energy recover', 'slug' => 'energy-recover', 'description' => 'Restores one character to 100 energy.', 'cost' => 80, 'effect' => 'Energy recover']);
        ShopItem::create(['name' => 'Beyond-the-hedge pass', 'slug' => 'beyond-the-hedge-pass', 'description' => 'A staff-recognized item that allows an eligible character to come from beyond the clans.', 'cost' => 500, 'effect' => 'Outsider access']);
        ShopItem::create(['name' => 'Moonlit eyes trait', 'slug' => 'moonlit-eyes-trait', 'description' => 'A rare cosmetic trait that can be added to one character.', 'cost' => 1000, 'effect' => 'Rare trait: Moonlit eyes']);
        ShopItem::create(['name' => 'Rare eye color', 'slug' => 'rare-eye-color', 'description' => 'Unlocks rare eye-color options such as heterochromia or violet eyes.', 'cost' => 250, 'effect' => 'Rare eye color']);
        ShopItem::create(['name' => 'Disability', 'slug' => 'disability', 'description' => 'Allows a disability detail to be recorded on one character.', 'cost' => 1000, 'effect' => 'Disability']);
        ShopItem::create(['name' => 'Male calico', 'slug' => 'male-calico', 'description' => 'Allows one male calico character.', 'cost' => 5000, 'effect' => 'Male calico']);
        ShopItem::create(['name' => 'Chimera or mosaicism', 'slug' => 'chimera-mosaicism', 'description' => 'Allows one character with a chimera or mosaicism trait.', 'cost' => 5000, 'effect' => 'Chimera/mosaicism']);
        ShopItem::create(['name' => 'Karpati, Roan, or Salmiak', 'slug' => 'karpati-roan-salmiak', 'description' => 'Allows one character with one of these coat conditions.', 'cost' => 2000, 'effect' => 'Karpati/Roan/Salmiak']);
        ShopItem::create(['name' => 'White sepia', 'slug' => 'white-sepia', 'description' => 'Allows the white sepia gene on one character.', 'cost' => 3000, 'effect' => 'White sepia']);
        ShopItem::create(['name' => 'Albino', 'slug' => 'albino', 'description' => 'Allows albinism on one character.', 'cost' => 5000, 'effect' => 'Albino']);
        ShopItem::create(['name' => 'Purebred', 'slug' => 'purebred', 'description' => 'Marks one character as purebred in character.', 'cost' => 3500, 'effect' => 'Purebred']);
        ShopItem::create(['name' => 'Flea treatment', 'slug' => 'flea-treatment', 'description' => 'Clears fleas from one character.', 'cost' => 75, 'effect' => 'Treat fleas']);
        ShopItem::create(['name' => 'Tick treatment', 'slug' => 'tick-treatment', 'description' => 'Clears ticks from one character.', 'cost' => 75, 'effect' => 'Treat ticks']);
        ShopItem::create(['name' => 'Illness treatment', 'slug' => 'illness-treatment', 'description' => 'Restores a character from sickness.', 'cost' => 150, 'effect' => 'Treat sickness']);
    }
}
