<?php

namespace Tests\Feature;

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
}
