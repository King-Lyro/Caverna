<?php

namespace Tests\Feature;

use App\Models\Character;
use App\Models\Inventory;
use App\Models\ShopItem;
use App\Models\User;
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
}
