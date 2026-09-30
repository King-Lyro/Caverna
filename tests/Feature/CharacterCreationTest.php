<?php

namespace Tests\Feature;

use App\Models\Character;
use App\Models\Inventory;
use App\Models\ShopItem;
use App\Models\User;
use App\Services\BreedingService;
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

    public function test_population_blocked_clan_is_disabled_in_creation_form(): void
    {
        $user = User::factory()->create(['status' => 'approved']);
        Character::create(['user_id' => $user->id, 'name' => 'Oak', 'sex' => 'female', 'age_moons' => 12, 'allegiance' => 'ThunderClan', 'role' => 'warrior', 'looks' => 'Bright coat.', 'appearance' => 'Appearance.', 'personality' => 'Personality.', 'history' => 'History.', 'status' => 'active']);

        $this->actingAs($user)->get(route('characters.create'))->assertOk()
            ->assertSee('value="ThunderClan" disabled', false)
            ->assertSee('value="RiverClan"', false);
    }

    public function test_fourth_non_adopted_character_requires_crickets(): void
    {
        $user = User::factory()->create(['status' => 'approved']);
        foreach (['One' => 'RiverClan', 'Two' => 'ShadowClan', 'Three' => 'WindClan'] as $name => $allegiance) {
            Character::create([
                'user_id' => $user->id,
                'name' => $name,
                'sex' => 'female',
                'age_moons' => 6,
                'allegiance' => $allegiance,
                'looks' => 'A quick description.',
                'appearance' => 'Appearance details.',
                'personality' => 'Personality details.',
                'history' => 'History details.',
                'adopted' => false,
            ]);
        }

        $response = $this->actingAs($user)->from(route('characters.create'))->post(route('characters.store'), $this->characterPayload());

        $response->assertRedirect(route('characters.create'))->assertSessionHas('error', 'You need 200 crickets to create another character.');
        $this->get(route('characters.create'))->assertOk()->assertSee('page-error')->assertSee('200 crickets');
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

    public function test_adopted_character_does_not_use_a_free_creation_slot(): void
    {
        $user = User::factory()->create(['status' => 'approved']);
        foreach (['One' => 'RiverClan', 'Two' => 'ShadowClan'] as $name => $allegiance) {
            Character::create(['user_id' => $user->id, 'name' => $name, 'sex' => 'female', 'age_moons' => 12, 'allegiance' => $allegiance, 'looks' => 'A bright coat.', 'appearance' => 'Appearance.', 'personality' => 'Personality.', 'history' => 'History.', 'adopted' => true, 'status' => 'active']);
        }

        $this->actingAs($user)->post(route('characters.store'), $this->characterPayload())->assertRedirect();
        $this->assertDatabaseCount('cricket_ledger', 0);
        $this->assertDatabaseHas('characters', ['user_id' => $user->id, 'name' => 'Ashfall', 'adopted' => false]);
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

        $this->actingAs($user)->post(route('characters.store'), $payload)->assertRedirect()->assertSessionHas('error', 'An outsider access item is required for this allegiance.');

        $item = ShopItem::create(['name' => 'Beyond-the-hedge pass', 'slug' => 'beyond-the-hedge-pass-test', 'description' => 'Outsider access.', 'cost' => 500, 'effect' => 'Outsider access']);
        $inventory = Inventory::create(['user_id' => $user->id, 'shop_item_id' => $item->id, 'quantity' => 1]);
        $this->actingAs($user)->post(route('characters.store'), $payload)->assertRedirect();

        $this->assertDatabaseHas('characters', ['user_id' => $user->id, 'allegiance' => 'outsider', 'role' => 'rogue']);
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

    public function test_creation_accepts_three_image_urls_without_file_uploads(): void
    {
        $user = User::factory()->create(['status' => 'approved']);
        $payload = $this->characterPayload();
        unset($payload['images']);
        $payload['image_urls'] = "https://example.com/one.jpg\nhttps://example.com/two.jpg\nhttps://example.com/three.jpg";

        $this->actingAs($user)->post(route('characters.store'), $payload)->assertRedirect();
        $this->assertCount(3, Character::firstOrFail()->images);
    }

    public function test_creation_accepts_three_uploaded_images(): void
    {
        $user = User::factory()->create(['status' => 'approved']);
        $payload = $this->characterPayload();
        $payload['images'] = [
            UploadedFile::fake()->image('one.png'),
            UploadedFile::fake()->image('two.png'),
            UploadedFile::fake()->image('three.png'),
        ];

        $this->actingAs($user)->post(route('characters.store'), $payload)->assertRedirect();
        $this->assertCount(3, Character::firstOrFail()->images);
    }

    public function test_biography_fields_require_two_hundred_fifty_words(): void
    {
        $user = User::factory()->create(['status' => 'approved']);
        $payload = $this->characterPayload();
        $payload['appearance'] = str_repeat('Short description. ', 40);

        $this->actingAs($user)->post(route('characters.store'), $payload)->assertSessionHasErrors('appearance');
    }

    public function test_creation_requires_a_forum_avatar(): void
    {
        $user = User::factory()->create(['status' => 'approved']);
        $payload = $this->characterPayload();
        unset($payload['forum_avatar']);

        $this->actingAs($user)->post(route('characters.store'), $payload)->assertSessionHasErrors('forum_avatar');
    }

    public function test_member_can_edit_a_character_without_changing_its_age(): void
    {
        $owner = User::factory()->create(['status' => 'approved']);
        $other = User::factory()->create(['status' => 'approved']);
        $this->actingAs($owner)->post(route('characters.store'), $this->characterPayload())->assertRedirect();
        $character = Character::firstOrFail();

        $this->actingAs($other)->get(route('characters.edit', $character))->assertForbidden();
        $this->actingAs($owner)->get(route('characters.edit', $character))->assertOk()->assertSee('name="name"', false);

        $payload = $this->characterPayload();
        $payload['name'] = 'Ashglow';
        $payload['age_moons'] = 80;
        $this->actingAs($owner)->patch(route('characters.update', $character), $payload)->assertRedirect(route('characters.show', $character));
        $this->assertDatabaseHas('characters', ['id' => $character->id, 'name' => 'Ashglow', 'age_moons' => 6]);
    }

    public function test_editing_pregnant_character_keeps_queen_role(): void
    {
        $owner = User::factory()->create(['status' => 'approved']);
        $other = User::factory()->create(['status' => 'approved']);
        $this->actingAs($owner)->post(route('characters.store'), $this->characterPayload())->assertRedirect();
        $female = Character::firstOrFail();
        $female->update(['age_moons' => 12]);
        $male = Character::create(['user_id' => $other->id, 'name' => 'Stone', 'sex' => 'male', 'age_moons' => 12, 'allegiance' => 'RiverClan', 'role' => 'warrior', 'looks' => 'Dark coat.', 'appearance' => 'Appearance.', 'personality' => 'Personality.', 'history' => 'History.', 'energy' => 100, 'status' => 'active']);
        app(BreedingService::class)->beginPregnancy($female, $male, false);

        $this->actingAs($owner)->patch(route('characters.update', $female), $this->characterPayload())->assertRedirect();
        $this->assertSame('queen', $female->fresh()->role);
    }

    public function test_applied_outsider_pass_unlocks_editing_into_an_outsider_role(): void
    {
        $owner = User::factory()->create(['status' => 'approved']);
        $this->actingAs($owner)->post(route('characters.store'), $this->characterPayload())->assertRedirect();
        $character = Character::firstOrFail();
        $item = ShopItem::create(['name' => 'Outsider pass', 'slug' => 'outsider-edit', 'description' => 'Access.', 'cost' => 500, 'effect' => 'Outsider access']);
        $inventory = Inventory::create(['user_id' => $owner->id, 'shop_item_id' => $item->id, 'quantity' => 1]);
        $this->actingAs($owner)->post(route('inventory.use', $inventory), ['character_id' => $character->id, 'outsider_role' => 'kittypet'])->assertRedirect();

        $this->actingAs($owner)->get(route('characters.edit', $character))->assertOk()->assertSee('value="Kittypet"', false);
        $payload = $this->characterPayload();
        $payload['allegiance'] = 'Kittypet';
        $this->actingAs($owner)->patch(route('characters.update', $character), $payload)->assertRedirect(route('characters.show', $character));
        $this->assertDatabaseHas('characters', ['id' => $character->id, 'allegiance' => 'outsider', 'role' => 'kittypet']);
        $this->assertDatabaseHas('inventories', ['id' => $inventory->id, 'quantity' => 0]);
    }

    public function test_outsider_cannot_return_to_clan_without_removing_access_item(): void
    {
        $owner = User::factory()->create(['status' => 'approved']);
        $item = ShopItem::create(['name' => 'Outsider pass', 'slug' => 'outsider-return-edit', 'description' => 'Access.', 'cost' => 500, 'effect' => 'Outsider access']);
        $inventory = Inventory::create(['user_id' => $owner->id, 'shop_item_id' => $item->id, 'quantity' => 1]);
        $this->actingAs($owner)->post(route('characters.store'), $this->characterPayload())->assertRedirect();
        $character = Character::firstOrFail();
        $this->actingAs($owner)->post(route('inventory.use', $inventory), ['character_id' => $character->id, 'outsider_role' => 'rogue'])->assertRedirect();

        $this->actingAs($owner)->patch(route('characters.update', $character), $this->characterPayload())->assertSessionHas('error');
        $this->assertSame('outsider', $character->fresh()->allegiance);
    }

    public function test_disability_can_only_be_edited_with_applied_item(): void
    {
        $owner = User::factory()->create(['status' => 'approved']);
        $this->actingAs($owner)->post(route('characters.store'), $this->characterPayload())->assertRedirect();
        $character = Character::firstOrFail();
        $payload = $this->characterPayload();
        $payload['disability'] = 'Limited sight in one eye.';

        $this->actingAs($owner)->patch(route('characters.update', $character), $payload)->assertSessionHasErrors('disability');
        $item = ShopItem::create(['name' => 'Disability', 'slug' => 'disability-profile', 'description' => 'Edit disability.', 'cost' => 1000, 'effect' => 'Disability']);
        $inventory = Inventory::create(['user_id' => $owner->id, 'shop_item_id' => $item->id, 'quantity' => 1]);
        $this->actingAs($owner)->post(route('inventory.use', $inventory), ['character_id' => $character->id, 'disability' => 'Limited sight in one eye.'])->assertRedirect();
        $this->actingAs($owner)->patch(route('characters.update', $character), $payload)->assertRedirect(route('characters.show', $character));
        $this->assertSame('Limited sight in one eye.', $character->fresh()->disability);
    }

    public function test_member_can_apply_purchased_rare_eye_and_disability_during_creation(): void
    {
        $user = User::factory()->create(['status' => 'approved']);
        $rareEye = ShopItem::create(['name' => 'Rare eye color', 'slug' => 'creation-rare-eye', 'description' => 'Rare.', 'cost' => 250, 'effect' => 'Rare eye color']);
        $disability = ShopItem::create(['name' => 'Disability', 'slug' => 'creation-disability', 'description' => 'Disability.', 'cost' => 1000, 'effect' => 'Disability']);
        $rareInventory = Inventory::create(['user_id' => $user->id, 'shop_item_id' => $rareEye->id, 'quantity' => 1]);
        $disabilityInventory = Inventory::create(['user_id' => $user->id, 'shop_item_id' => $disability->id, 'quantity' => 1]);
        $payload = $this->characterPayload();
        $payload['eye_color'] = 'Violet';
        $payload['disability'] = 'Limited sight in one eye.';
        $payload['enhancements'] = [$rareInventory->id, $disabilityInventory->id];

        $this->actingAs($user)->post(route('characters.store'), $payload)->assertRedirect();

        $character = Character::firstOrFail();
        $this->assertSame('Violet', $character->eye_color);
        $this->assertSame('Limited sight in one eye.', $character->disability);
        $this->assertSame(2, $character->appliedItems()->count());
        $this->assertSame(0, $rareInventory->fresh()->quantity);
        $this->assertSame(0, $disabilityInventory->fresh()->quantity);
    }

    public function test_failed_male_calico_creation_does_not_consume_the_item(): void
    {
        $user = User::factory()->create(['status' => 'approved']);
        $item = ShopItem::create(['name' => 'Male calico', 'slug' => 'creation-male-calico', 'description' => 'Male calico.', 'cost' => 5000, 'effect' => 'Male calico']);
        $inventory = Inventory::create(['user_id' => $user->id, 'shop_item_id' => $item->id, 'quantity' => 1]);
        $payload = $this->characterPayload();
        $payload['enhancements'] = [$inventory->id];

        $this->actingAs($user)->post(route('characters.store'), $payload)->assertSessionHas('error');
        $this->assertDatabaseCount('characters', 0);
        $this->assertSame(1, $inventory->fresh()->quantity);
    }

    public function test_rare_eye_choice_without_item_is_rejected(): void
    {
        $user = User::factory()->create(['status' => 'approved']);
        $payload = $this->characterPayload();
        $payload['eye_color'] = 'Violet';

        $this->actingAs($user)->post(route('characters.store'), $payload)->assertSessionHasErrors('eye_color');
    }

    public function test_member_cannot_edit_to_rare_eye_color_without_applied_item(): void
    {
        $user = User::factory()->create(['status' => 'approved']);
        $this->actingAs($user)->post(route('characters.store'), $this->characterPayload())->assertRedirect();
        $character = Character::firstOrFail();
        $payload = $this->characterPayload();
        $payload['eye_color'] = 'Violet';

        $this->actingAs($user)->patch(route('characters.update', $character), $payload)->assertSessionHasErrors('eye_color');
        $this->assertSame('Amber', $character->fresh()->eye_color);
    }

    public function test_member_can_apply_rare_eye_item_while_editing(): void
    {
        $user = User::factory()->create(['status' => 'approved']);
        $this->actingAs($user)->post(route('characters.store'), $this->characterPayload())->assertRedirect();
        $character = Character::firstOrFail();
        $item = ShopItem::create(['name' => 'Rare eye color', 'slug' => 'edit-rare-eye', 'description' => 'Rare color.', 'cost' => 250, 'effect' => 'Rare eye color']);
        $inventory = Inventory::create(['user_id' => $user->id, 'shop_item_id' => $item->id, 'quantity' => 1]);
        $payload = $this->characterPayload();
        $payload['eye_color'] = 'Violet';
        $payload['enhancements'] = [$inventory->id];

        $this->actingAs($user)->get(route('characters.edit', $character))->assertOk()->assertSee('name="enhancements[]"', false);
        $this->actingAs($user)->patch(route('characters.update', $character), $payload)->assertRedirect(route('characters.show', $character));
        $this->assertSame('Violet', $character->fresh()->eye_color);
        $this->assertSame(0, $inventory->fresh()->quantity);
        $this->assertDatabaseHas('character_items', ['character_id' => $character->id, 'shop_item_id' => $item->id]);
    }

    private function characterPayload(): array
    {
        $text = str_repeat('This character carries a careful history through the changing seasons of the forest. ', 35);

        return [
            'name' => 'Ashfall',
            'sex' => 'female',
            'eye_color' => 'Amber',
            'forum_avatar' => UploadedFile::fake()->image('avatar.png', 128, 128),
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
