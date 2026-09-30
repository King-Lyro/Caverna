<?php

namespace Tests\Feature;

use App\Models\Character;
use App\Models\Inventory;
use App\Models\ShopItem;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ShopPurchaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_member_can_buy_an_item_and_receive_inventory_quantity(): void
    {
        $user = User::factory()->create(['status' => 'approved']);
        $item = ShopItem::create(['name' => 'Moss poultice', 'slug' => 'moss-poultice', 'description' => 'Restore energy.', 'cost' => 100]);
        DB::table('cricket_ledger')->insert(['user_id' => $user->id, 'amount' => 150, 'type' => 'grant', 'description' => 'Test grant', 'created_at' => now(), 'updated_at' => now()]);

        $response = $this->actingAs($user)->post(route('shop.purchase', $item));

        $response->assertRedirect();
        $this->assertDatabaseHas('cricket_ledger', ['user_id' => $user->id, 'amount' => -100, 'type' => 'shop_purchase']);
        $this->assertDatabaseHas('inventories', ['user_id' => $user->id, 'shop_item_id' => $item->id, 'quantity' => 1]);
    }

    public function test_cricket_ledger_renders_legacy_entries_without_dates(): void
    {
        $user = User::factory()->create(['status' => 'approved']);
        DB::table('cricket_ledger')->insert(['user_id' => $user->id, 'amount' => 10, 'type' => 'grant', 'description' => 'Welcome grant']);

        $this->actingAs($user)->get(route('crickets'))->assertOk()
            ->assertSee('Welcome grant')->assertSee('Date unavailable');
    }

    public function test_matching_item_icons_appear_in_shop_and_on_character_profile(): void
    {
        $user = User::factory()->create(['status' => 'approved']);
        $item = ShopItem::create(['name' => 'Rare eye color', 'slug' => 'rare-eye-color-icon', 'description' => 'Rare color.', 'cost' => 250, 'effect' => 'Rare eye color']);
        $character = Character::create(['user_id' => $user->id, 'name' => 'Ash', 'sex' => 'female', 'age_moons' => 12, 'allegiance' => 'ThunderClan', 'looks' => 'A bright coat.', 'appearance' => 'Appearance.', 'personality' => 'Personality.', 'history' => 'History.', 'energy' => 100, 'status' => 'active']);
        $inventory = Inventory::create(['user_id' => $user->id, 'shop_item_id' => $item->id, 'quantity' => 1]);

        $this->get(route('shop'))->assertOk()->assertSee('rare_eye_color.png');
        $this->actingAs($user)->post(route('inventory.use', $inventory), ['character_id' => $character->id, 'eye_color' => 'Violet'])->assertRedirect();
        $this->get(route('characters.show', $character))->assertOk()->assertSee('rare_eye_color.png');
    }

    public function test_seeded_enhancement_prices_and_available_icons_match_the_spec(): void
    {
        $this->seed(DatabaseSeeder::class);
        foreach ([
            'Rare eye color' => [250, 'rare_eye_color.png'],
            'Disability' => [1000, 'dsiability.png'],
            'Energy return' => [20, 'energy_return.png'],
            'Energy recover' => [80, 'energy_recover.png'],
            'Outsider access' => [500, 'outsider_allegiance.png'],
            'Male calico' => [5000, 'male_calico.png'],
            'Chimera/mosaicism' => [5000, 'chimera_mosaicism.png'],
            'Karpati/Roan/Salmiak' => [2000, 'karpati_roan_salmiak.png'],
            'White sepia' => [3000, 'white_sepia.png'],
            'Albino' => [5000, 'albino.png'],
            'Purebred' => [3500, 'purebred.png'],
        ] as $effect => [$cost, $icon]) {
            $item = ShopItem::where('effect', $effect)->firstOrFail();
            $this->assertSame($cost, $item->cost);
            $this->assertStringContainsString($icon, $item->iconUrl());
        }
        $this->assertNull(ShopItem::where('slug', 'moss-poultice')->firstOrFail()->iconUrl());
    }

    public function test_rare_eye_and_disability_items_save_member_chosen_details(): void
    {
        $user = User::factory()->create(['status' => 'approved']);
        $character = Character::create(['user_id' => $user->id, 'name' => 'Ash', 'sex' => 'female', 'age_moons' => 12, 'allegiance' => 'ThunderClan', 'eye_color' => 'Amber', 'looks' => 'Bright coat.', 'appearance' => 'Appearance.', 'personality' => 'Personality.', 'history' => 'History.', 'energy' => 100, 'status' => 'active']);
        $rareEye = ShopItem::create(['name' => 'Rare eye color', 'slug' => 'rare-eye-choice', 'description' => 'Rare color.', 'cost' => 250, 'effect' => 'Rare eye color']);
        $disability = ShopItem::create(['name' => 'Disability', 'slug' => 'disability-choice', 'description' => 'Disability.', 'cost' => 1000, 'effect' => 'Disability']);
        $rareInventory = Inventory::create(['user_id' => $user->id, 'shop_item_id' => $rareEye->id, 'quantity' => 1]);
        $disabilityInventory = Inventory::create(['user_id' => $user->id, 'shop_item_id' => $disability->id, 'quantity' => 1]);

        $this->actingAs($user)->post(route('inventory.use', $rareInventory), ['character_id' => $character->id, 'eye_color' => 'Violet'])->assertRedirect();
        $this->actingAs($user)->post(route('inventory.use', $disabilityInventory), ['character_id' => $character->id, 'disability' => 'Limited sight in one eye.'])->assertRedirect();

        $this->assertDatabaseHas('characters', ['id' => $character->id, 'eye_color' => 'Violet', 'disability' => 'Limited sight in one eye.']);
    }

    public function test_male_calico_item_cannot_be_used_on_a_she_cat(): void
    {
        $user = User::factory()->create(['status' => 'approved']);
        $character = Character::create(['user_id' => $user->id, 'name' => 'Ash', 'sex' => 'female', 'age_moons' => 12, 'allegiance' => 'ThunderClan', 'looks' => 'Bright coat.', 'appearance' => 'Appearance.', 'personality' => 'Personality.', 'history' => 'History.', 'energy' => 100, 'status' => 'active']);
        $item = ShopItem::create(['name' => 'Male calico', 'slug' => 'male-calico-limit', 'description' => 'Male calico.', 'cost' => 5000, 'effect' => 'Male calico']);
        $inventory = Inventory::create(['user_id' => $user->id, 'shop_item_id' => $item->id, 'quantity' => 1]);

        $this->actingAs($user)->post(route('inventory.use', $inventory), ['character_id' => $character->id])->assertSessionHas('error');
        $this->assertSame(1, $inventory->fresh()->quantity);
        $this->assertFalse($character->fresh()->male_calico);
    }

    public function test_deceased_character_cannot_receive_a_permanent_enhancement(): void
    {
        $user = User::factory()->create(['status' => 'approved']);
        $character = Character::create(['user_id' => $user->id, 'name' => 'Ash', 'sex' => 'female', 'age_moons' => 161, 'allegiance' => 'ThunderClan', 'looks' => 'Bright coat.', 'appearance' => 'Appearance.', 'personality' => 'Personality.', 'history' => 'History.', 'energy' => 0, 'status' => 'deceased']);
        $item = ShopItem::create(['name' => 'Purebred', 'slug' => 'purebred-after-death', 'description' => 'Purebred.', 'cost' => 3500, 'effect' => 'Purebred']);
        $inventory = Inventory::create(['user_id' => $user->id, 'shop_item_id' => $item->id, 'quantity' => 1]);

        $this->actingAs($user)->post(route('inventory.use', $inventory), ['character_id' => $character->id])->assertSessionHas('error');
        $this->assertSame(1, $inventory->fresh()->quantity);
        $this->assertDatabaseCount('character_items', 0);
    }

    public function test_removing_rare_eye_item_requires_an_ordinary_eye_color(): void
    {
        $user = User::factory()->create(['status' => 'approved']);
        $character = Character::create(['user_id' => $user->id, 'name' => 'Ash', 'sex' => 'female', 'age_moons' => 12, 'allegiance' => 'ThunderClan', 'eye_color' => 'Amber', 'looks' => 'Bright coat.', 'appearance' => 'Appearance.', 'personality' => 'Personality.', 'history' => 'History.', 'energy' => 100, 'status' => 'active']);
        $item = ShopItem::create(['name' => 'Rare eye color', 'slug' => 'rare-eye-removal', 'description' => 'Rare.', 'cost' => 250, 'effect' => 'Rare eye color']);
        $inventory = Inventory::create(['user_id' => $user->id, 'shop_item_id' => $item->id, 'quantity' => 1]);
        $this->actingAs($user)->post(route('inventory.use', $inventory), ['character_id' => $character->id, 'eye_color' => 'Violet'])->assertRedirect();
        $applied = $character->appliedItems()->firstOrFail();

        $this->actingAs($user)->delete(route('characters.items.destroy', [$character, $applied]), ['eye_color' => 'Purple'])->assertSessionHasErrors('eye_color');
        $this->actingAs($user)->delete(route('characters.items.destroy', [$character, $applied]), ['eye_color' => 'Amber'])->assertRedirect();
        $this->assertSame('Amber', $character->fresh()->eye_color);
        $this->assertSame(0, $inventory->fresh()->quantity);
        $this->assertSoftDeleted('character_items', ['id' => $applied->id]);
    }

    public function test_member_can_use_energy_item_on_their_character(): void
    {
        $user = User::factory()->create(['status' => 'approved']);
        $item = ShopItem::create(['name' => 'Moss poultice', 'slug' => 'moss-poultice', 'description' => 'Restore energy.', 'cost' => 100, 'effect' => 'Energy restoration']);
        $inventory = Inventory::create(['user_id' => $user->id, 'shop_item_id' => $item->id, 'quantity' => 1]);
        $character = Character::create(['user_id' => $user->id, 'name' => 'Ash', 'sex' => 'female', 'age_moons' => 12, 'allegiance' => 'ThunderClan', 'looks' => 'A bright coat.', 'appearance' => 'Appearance.', 'personality' => 'Personality.', 'history' => 'History.', 'energy' => 90, 'status' => 'active']);

        $this->actingAs($user)->post(route('inventory.use', $inventory), ['character_id' => $character->id])->assertRedirect();

        $this->assertDatabaseHas('characters', ['id' => $character->id, 'energy' => 100]);
        $this->assertDatabaseHas('inventories', ['id' => $inventory->id, 'quantity' => 0]);
    }

    public function test_member_can_apply_rare_trait_and_clear_an_ailment(): void
    {
        $user = User::factory()->create(['status' => 'approved']);
        $traitItem = ShopItem::create(['name' => 'Moonlit eyes trait', 'slug' => 'moonlit-eyes-trait-test', 'description' => 'Rare trait.', 'cost' => 1000, 'effect' => 'Rare trait: Moonlit eyes']);
        $treatment = ShopItem::create(['name' => 'Flea treatment', 'slug' => 'flea-treatment-test', 'description' => 'Treatment.', 'cost' => 75, 'effect' => 'Treat fleas']);
        $traitInventory = Inventory::create(['user_id' => $user->id, 'shop_item_id' => $traitItem->id, 'quantity' => 1]);
        $treatmentInventory = Inventory::create(['user_id' => $user->id, 'shop_item_id' => $treatment->id, 'quantity' => 1]);
        $character = Character::create(['user_id' => $user->id, 'name' => 'Ash', 'sex' => 'female', 'age_moons' => 12, 'allegiance' => 'ThunderClan', 'looks' => 'A bright coat.', 'appearance' => 'Appearance.', 'personality' => 'Personality.', 'history' => 'History.', 'energy' => 100, 'ailments' => ['fleas'], 'health_status' => 'unwell', 'status' => 'active']);

        $this->actingAs($user)->post(route('inventory.use', $traitInventory), ['character_id' => $character->id])->assertRedirect();
        $this->actingAs($user)->post(route('inventory.use', $treatmentInventory), ['character_id' => $character->id])->assertRedirect();

        $character = $character->fresh();
        $this->assertContains('Moonlit eyes', $character->traits);
        $this->assertSame([], $character->ailments);
        $this->assertSame('healthy', $character->health_status);
    }

    public function test_member_can_use_energy_return_and_energy_recover_items(): void
    {
        $user = User::factory()->create(['status' => 'approved']);
        $returnItem = ShopItem::create(['name' => 'Energy return', 'slug' => 'energy-return-test', 'description' => 'Return energy.', 'cost' => 20, 'effect' => 'Energy return']);
        $recoverItem = ShopItem::create(['name' => 'Energy recover', 'slug' => 'energy-recover-test', 'description' => 'Recover energy.', 'cost' => 80, 'effect' => 'Energy recover']);
        $returnInventory = Inventory::create(['user_id' => $user->id, 'shop_item_id' => $returnItem->id, 'quantity' => 1]);
        $recoverInventory = Inventory::create(['user_id' => $user->id, 'shop_item_id' => $recoverItem->id, 'quantity' => 1]);
        $character = Character::create(['user_id' => $user->id, 'name' => 'Ash', 'sex' => 'female', 'age_moons' => 12, 'allegiance' => 'ThunderClan', 'looks' => 'A bright coat.', 'appearance' => 'Appearance.', 'personality' => 'Personality.', 'history' => 'History.', 'energy' => 50, 'status' => 'active']);

        $this->actingAs($user)->post(route('inventory.use', $returnInventory), ['character_id' => $character->id])->assertRedirect();
        $this->assertSame(60, $character->fresh()->energy);
        $this->actingAs($user)->post(route('inventory.use', $recoverInventory), ['character_id' => $character->id])->assertRedirect();
        $this->assertSame(100, $character->fresh()->energy);
    }

    public function test_member_can_apply_a_special_profile_item_once(): void
    {
        $user = User::factory()->create(['status' => 'approved']);
        $item = ShopItem::create(['name' => 'Purebred', 'slug' => 'purebred-test', 'description' => 'Purebred.', 'cost' => 3500, 'effect' => 'Purebred']);
        $inventory = Inventory::create(['user_id' => $user->id, 'shop_item_id' => $item->id, 'quantity' => 1]);
        $character = Character::create(['user_id' => $user->id, 'name' => 'Ash', 'sex' => 'female', 'age_moons' => 12, 'allegiance' => 'ThunderClan', 'looks' => 'A bright coat.', 'appearance' => 'Appearance.', 'personality' => 'Personality.', 'history' => 'History.', 'energy' => 100, 'status' => 'active']);

        $this->actingAs($user)->post(route('inventory.use', $inventory), ['character_id' => $character->id])->assertRedirect();

        $this->assertContains('Purebred', $character->fresh()->traits);
        $this->assertDatabaseHas('character_items', ['character_id' => $character->id, 'shop_item_id' => $item->id]);
    }

    public function test_owner_can_remove_an_applied_item_without_refunding_it(): void
    {
        $user = User::factory()->create(['status' => 'approved']);
        $item = ShopItem::create(['name' => 'Purebred', 'slug' => 'purebred-removal', 'description' => 'Purebred.', 'cost' => 3500, 'effect' => 'Purebred']);
        $inventory = Inventory::create(['user_id' => $user->id, 'shop_item_id' => $item->id, 'quantity' => 1]);
        $character = Character::create(['user_id' => $user->id, 'name' => 'Ash', 'sex' => 'female', 'age_moons' => 12, 'allegiance' => 'ThunderClan', 'looks' => 'Bright coat.', 'appearance' => 'Appearance.', 'personality' => 'Personality.', 'history' => 'History.', 'energy' => 100, 'status' => 'active']);
        $this->actingAs($user)->post(route('inventory.use', $inventory), ['character_id' => $character->id])->assertRedirect();
        $applied = $character->appliedItems()->firstOrFail();

        $this->actingAs($user)->delete(route('characters.items.destroy', [$character, $applied]))->assertRedirect();
        $this->assertFalse($character->fresh()->purebred);
        $this->assertSame(0, $inventory->fresh()->quantity);
        $this->assertSoftDeleted('character_items', ['id' => $applied->id]);
    }

    public function test_removed_outsider_pass_returns_character_to_a_clan_without_refund(): void
    {
        $user = User::factory()->create(['status' => 'approved']);
        $item = ShopItem::create(['name' => 'Outsider pass', 'slug' => 'outsider-return', 'description' => 'Access.', 'cost' => 500, 'effect' => 'Outsider access']);
        $inventory = Inventory::create(['user_id' => $user->id, 'shop_item_id' => $item->id, 'quantity' => 1]);
        $character = Character::create(['user_id' => $user->id, 'name' => 'Ash', 'sex' => 'female', 'age_moons' => 12, 'allegiance' => 'ThunderClan', 'role' => 'warrior', 'looks' => 'Bright coat.', 'appearance' => 'Appearance.', 'personality' => 'Personality.', 'history' => 'History.', 'energy' => 100, 'status' => 'active']);
        $this->actingAs($user)->post(route('inventory.use', $inventory), ['character_id' => $character->id, 'outsider_role' => 'rogue'])->assertRedirect();
        $applied = $character->appliedItems()->firstOrFail();

        $this->actingAs($user)->delete(route('characters.items.destroy', [$character, $applied]), ['clan_allegiance' => 'RiverClan'])->assertRedirect();
        $this->assertDatabaseHas('characters', ['id' => $character->id, 'allegiance' => 'RiverClan', 'role' => 'warrior']);
        $this->assertSoftDeleted('character_items', ['id' => $applied->id]);
        $this->assertSame(0, $inventory->fresh()->quantity);

        $inventory->update(['quantity' => 1]);
        $this->actingAs($user)->post(route('inventory.use', $inventory), ['character_id' => $character->id])->assertSessionHas('error');
    }

    public function test_energy_consumable_can_be_used_twice_on_a_character(): void
    {
        $user = User::factory()->create(['status' => 'approved']);
        $item = ShopItem::create(['name' => 'Energy return', 'slug' => 'energy-repeat', 'description' => 'Return.', 'cost' => 20, 'effect' => 'Energy return']);
        $inventory = Inventory::create(['user_id' => $user->id, 'shop_item_id' => $item->id, 'quantity' => 2]);
        $character = Character::create(['user_id' => $user->id, 'name' => 'Ash', 'sex' => 'female', 'age_moons' => 12, 'allegiance' => 'ThunderClan', 'looks' => 'Bright coat.', 'appearance' => 'Appearance.', 'personality' => 'Personality.', 'history' => 'History.', 'energy' => 50, 'status' => 'active']);

        $this->actingAs($user)->post(route('inventory.use', $inventory), ['character_id' => $character->id])->assertRedirect();
        $this->actingAs($user)->post(route('inventory.use', $inventory), ['character_id' => $character->id])->assertRedirect();
        $this->assertSame(70, $character->fresh()->energy);
        $this->assertSame(0, $inventory->fresh()->quantity);
    }

    public function test_time_freeze_item_stops_lifecycle_until_removed(): void
    {
        $user = User::factory()->create(['status' => 'approved']);
        $item = ShopItem::create(['name' => 'Time freeze', 'slug' => 'time-freeze-test', 'description' => 'Hold time.', 'cost' => 500, 'effect' => 'Time freeze']);
        $inventory = Inventory::create(['user_id' => $user->id, 'shop_item_id' => $item->id, 'quantity' => 1]);
        $character = Character::create(['user_id' => $user->id, 'name' => 'Ash', 'sex' => 'female', 'age_moons' => 12, 'allegiance' => 'ThunderClan', 'looks' => 'Bright coat.', 'appearance' => 'Appearance.', 'personality' => 'Personality.', 'history' => 'History.', 'energy' => 100, 'status' => 'active']);

        $this->actingAs($user)->post(route('inventory.use', $inventory), ['character_id' => $character->id])->assertRedirect();
        $this->assertDatabaseHas('characters', ['id' => $character->id, 'is_frozen' => true, 'frozen_reason' => 'item']);
        $applied = $character->appliedItems()->firstOrFail();
        $this->actingAs($user)->delete(route('characters.items.destroy', [$character, $applied]))->assertRedirect();
        $this->assertFalse($character->fresh()->is_frozen);
        $this->assertSoftDeleted('character_items', ['id' => $applied->id]);
        $this->assertSame(0, $inventory->fresh()->quantity);
    }

    public function test_energy_item_cannot_revive_a_deceased_character(): void
    {
        $user = User::factory()->create(['status' => 'approved']);
        $item = ShopItem::create(['name' => 'Energy return', 'slug' => 'energy-no-revival', 'description' => 'Return energy.', 'cost' => 20, 'effect' => 'Energy return']);
        $inventory = Inventory::create(['user_id' => $user->id, 'shop_item_id' => $item->id, 'quantity' => 1]);
        $character = Character::create(['user_id' => $user->id, 'name' => 'Ash', 'sex' => 'female', 'age_moons' => 161, 'allegiance' => 'ThunderClan', 'looks' => 'Bright coat.', 'appearance' => 'Appearance.', 'personality' => 'Personality.', 'history' => 'History.', 'energy' => 0, 'status' => 'deceased']);

        $this->actingAs($user)->post(route('inventory.use', $inventory), ['character_id' => $character->id])->assertSessionHas('error');
        $this->assertDatabaseHas('characters', ['id' => $character->id, 'status' => 'deceased', 'energy' => 0]);
        $this->assertSame(1, $inventory->fresh()->quantity);
    }
}
