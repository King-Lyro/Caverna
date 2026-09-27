<?php

namespace Tests\Feature;

use App\Models\ShopItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeAndStaticPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_placeholder_footer_pages_are_available(): void
    {
        $this->get(route('content.page', 'rules'))->assertOk();
        $this->get(route('content.page', 'privacy'))->assertOk()->assertSee('Privacy')->assertSee('page-heading page-heading-theme');
        $this->get(route('content.page', 'contact'))->assertOk()->assertSee('Contact');
    }

    public function test_forum_navigation_link_is_active_on_forum_page(): void
    {
        $this->get(route('forum'))->assertOk()->assertSee('class="is-active"', false);
    }

    public function test_theme_selection_is_stored_in_session(): void
    {
        $this->withSession(['theme' => 'spring'])->post(route('theme.update', 'winter'))->assertRedirect()->assertSessionHas('theme', 'winter');
    }

    public function test_navigation_groups_public_and_member_links_without_hiding_destinations(): void
    {
        $this->get(route('home'))->assertOk()
            ->assertSee('<summary', false)->assertSee('Explore')->assertSee('Market')
            ->assertSee(route('content.page', 'rules'))
            ->assertSee(route('content.page', 'guide'))
            ->assertSee(route('characters.memorial'))
            ->assertSee(route('adoption.index'));

        $user = User::factory()->create(['status' => 'approved']);
        $this->actingAs($user)->get(route('dashboard'))->assertOk()
            ->assertSee(route('inventory'))
            ->assertSee(route('crickets'))
            ->assertSee(route('account.edit'))
            ->assertSee(route('notifications'))
            ->assertSee('method="POST" action="'.route('logout').'"', false);
    }

    public function test_shop_catalogue_renders_product_card_structure(): void
    {
        $this->get(route('shop'))->assertOk()->assertSee('class="shop-section"', false)
            ->assertSee('class="shop-grid"', false)
            ->assertSee('No items are available right now.');

        $item = ShopItem::create(['name' => 'Trail pass', 'slug' => 'trail-pass', 'description' => 'Access the distant paths.', 'cost' => 500, 'effect' => 'Outsider access']);
        $user = User::factory()->create(['status' => 'approved']);

        $this->actingAs($user)->get(route('shop'))->assertOk()
            ->assertSee('class="shop-card"', false)
            ->assertSee('Trail pass')
            ->assertSee('500')
            ->assertSee(route('shop.purchase', $item));
    }
}
