<?php

namespace Tests\Feature;

use App\Models\Character;
use App\Models\Inventory;
use App\Models\ShopItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CharacterCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_member_can_open_character_creation(): void
    {
        $user = User::factory()->create(['status' => 'approved']);

        $this->actingAs($user)->get(route('characters.create'))->assertOk();
    }

    public function test_outsider_allegiances_only_appear_with_an_available_access_item(): void
    {
        $user = User::factory()->create(['status' => 'approved']);
        $item = ShopItem::create(['name' => 'Beyond-the-hedge pass', 'slug' => 'beyond-the-hedge-pass-visibility', 'description' => 'Outsider access.', 'cost' => 500, 'effect' => 'Outsider access']);
        $inventory = Inventory::create(['user_id' => $user->id, 'shop_item_id' => $item->id, 'quantity' => 0]);

        $this->actingAs($user)->get(route('characters.create'))->assertOk()
            ->assertSee('value="ThunderClan"', false)
            ->assertDontSee('value="Kittypet"', false)
            ->assertDontSee('value="Loner"', false)
            ->assertDontSee('value="Rogue"', false);

        $inventory->update(['quantity' => 1]);

        $this->actingAs($user)->get(route('characters.create'))->assertOk()
            ->assertSee('value="Kittypet"', false)
            ->assertSee('value="Loner"', false)
            ->assertSee('value="Rogue"', false);
    }

    public function test_fourth_non_adopted_character_requires_crickets(): void
    {
        $user = User::factory()->create(['status' => 'approved']);
        foreach (['One', 'Two', 'Three'] as $name) {
            Character::create([
                'user_id' => $user->id,
                'name' => $name,
                'sex' => 'female',
                'age_moons' => 6,
                'allegiance' => 'ThunderClan',
                'looks' => 'A quick description.',
                'appearance' => 'Appearance details.',
                'personality' => 'Personality details.',
                'history' => 'History details.',
                'adopted' => false,
            ]);
        }

        $response = $this->actingAs($user)->post(route('characters.store'), $this->characterPayload());

        $response->assertStatus(422);
        $response->assertSee('200 crickets');
        $this->assertDatabaseCount('characters', 3);
    }

    public function test_first_character_is_created_with_full_energy_without_a_charge(): void
    {
        $user = User::factory()->create(['status' => 'approved']);

        $response = $this->actingAs($user)->post(route('characters.store'), $this->characterPayload());

        $response->assertRedirect();
        $this->assertDatabaseHas('characters', ['user_id' => $user->id, 'name' => 'Ashfall', 'energy' => 100]);
        $this->assertDatabaseCount('cricket_ledger', 0);
    }

    public function test_looks_cannot_exceed_fifteen_words(): void
    {
        $user = User::factory()->create(['status' => 'approved']);
        $payload = $this->characterPayload();
        $payload['looks'] = 'one two three four five six seven eight nine ten eleven twelve thirteen fourteen fifteen sixteen';

        $this->actingAs($user)->post(route('characters.store'), $payload)->assertSessionHasErrors('looks');
    }

    public function test_character_profile_renders_gallery_mate_and_kits(): void
    {
        $user = User::factory()->create(['status' => 'approved']);
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'Ashfall',
            'sex' => 'female',
            'age_moons' => 12,
            'allegiance' => 'ThunderClan',
            'looks' => 'A charcoal coat.',
            'appearance' => 'Appearance.',
            'personality' => 'Personality.',
            'history' => 'History.',
            'images' => ['https://example.com/one.jpg', 'https://example.com/two.jpg', 'https://example.com/three.jpg'],
            'mate' => 'Rainwhisker',
            'kits' => 'Two kits',
            'status' => 'active',
        ]);

        $this->get(route('characters.show', $character))->assertOk()->assertSee('Rainwhisker')->assertSee('Two kits')->assertSee('one.jpg');
    }

    public function test_outsider_creation_requires_and_consumes_an_access_item(): void
    {
        $user = User::factory()->create(['status' => 'approved']);
        $payload = $this->characterPayload();
        $payload['allegiance'] = 'Rogue';

        $this->actingAs($user)->post(route('characters.store'), $payload)->assertStatus(422);

        $item = ShopItem::create(['name' => 'Beyond-the-hedge pass', 'slug' => 'beyond-the-hedge-pass-test', 'description' => 'Outsider access.', 'cost' => 500, 'effect' => 'Outsider access']);
        $inventory = Inventory::create(['user_id' => $user->id, 'shop_item_id' => $item->id, 'quantity' => 1]);
        $this->actingAs($user)->post(route('characters.store'), $payload)->assertRedirect();

        $this->assertDatabaseHas('characters', ['user_id' => $user->id, 'allegiance' => 'Rogue']);
        $this->assertDatabaseHas('inventories', ['id' => $inventory->id, 'quantity' => 0]);
    }

    public function test_forum_avatar_can_be_uploaded_with_a_character(): void
    {
        $user = User::factory()->create(['status' => 'approved']);
        $payload = $this->characterPayload();
        $payload['forum_avatar'] = UploadedFile::fake()->image('avatar.png', 128, 128);

        $this->actingAs($user)->post(route('characters.store'), $payload)->assertRedirect();

        $this->assertDatabaseMissing('characters', ['user_id' => $user->id, 'forum_avatar_path' => null]);
    }

    private function characterPayload(): array
    {
        $text = str_repeat('This character carries a careful history through the changing seasons of the forest. ', 35);

        return [
            'name' => 'Ashfall',
            'sex' => 'female',
            'age_moons' => 6,
            'allegiance' => 'ThunderClan',
            'looks' => 'A charcoal coat with a pale blaze.',
            'images' => ['https://example.com/one.jpg', 'https://example.com/two.jpg', 'https://example.com/three.jpg'],
            'appearance' => $text,
            'personality' => $text,
            'history' => $text,
        ];
    }
}
