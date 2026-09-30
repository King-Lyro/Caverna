<?php

namespace Tests\Feature;

use App\Models\ContentPage;
use App\Models\Character;
use App\Models\ForumBoard;
use App\Models\ForumCategory;
use App\Models\ShopItem;
use App\Models\Rule;
use App\Models\RuleCategory;
use App\Models\User;
use App\Models\WorldPage;
use App\Support\TerritoryMap;
use Database\Seeders\MapForumSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function test_at_a_glance_uses_live_story_and_population_counts(): void
    {
        $user = User::factory()->create();
        foreach (['ThunderClan', 'RiverClan', 'ShadowClan', 'WindClan'] as $clan) {
            Character::create(['user_id' => $user->id, 'name' => $clan.' cat', 'sex' => 'female', 'age_moons' => 12, 'allegiance' => $clan, 'role' => 'warrior', 'looks' => 'A careful gaze.', 'appearance' => 'Appearance.', 'personality' => 'Personality.', 'history' => 'History.', 'energy' => 100, 'status' => 'active']);
        }
        Character::create(['user_id' => $user->id, 'name' => 'Outsider cat', 'sex' => 'male', 'age_moons' => 12, 'allegiance' => 'outsider', 'role' => 'loner', 'looks' => 'A careful gaze.', 'appearance' => 'Appearance.', 'personality' => 'Personality.', 'history' => 'History.', 'energy' => 100, 'status' => 'active']);
        $category = ForumCategory::create(['name' => 'Stories', 'sort_order' => 1]);
        $board = $category->boards()->create(['name' => 'Story board', 'slug' => 'story-board', 'is_ic' => true]);
        $board->threads()->create(['user_id' => $user->id, 'title' => 'Open story', 'slug' => 'open-story']);

        $this->get(route('home'))->assertOk()
            ->assertSee('Open stories</dt><dd>1', false)
            ->assertSee('Living characters</dt><dd>5', false)
            ->assertSee('Clan population</dt><dd>4', false)
            ->assertSee('Outsiders</dt><dd>1', false);
    }

    public function test_map_locations_link_to_seeded_forum_boards(): void
    {
        $this->seed(MapForumSeeder::class);
        $this->seed(MapForumSeeder::class);
        $this->assertFileExists(public_path('images/map.png'));

        $map = $this->get(route('content.page', 'map'))->assertOk()
            ->assertSee('map-content')->assertSee('Clan camps')->assertSee('Territory landmarks')
            ->assertSee('usemap="#territory-map"', false)->assertSee('<map name="territory-map">', false);
        foreach (TerritoryMap::locations() as $location) {
            $map->assertSee(route('forum.board', $location['slug']));
            $this->assertDatabaseHas('forum_boards', ['slug' => $location['slug'], 'is_ic' => true]);
        }
        $this->assertSame(4, ForumCategory::where('name', 'Clan camps')->firstOrFail()->boards()->count());
        $this->assertSame(count(TerritoryMap::locations()) - 4, ForumCategory::where('name', 'Territory landmarks')->firstOrFail()->boards()->count());
        $this->assertSame(count(TerritoryMap::locations()), ForumBoard::count());
        $this->get(route('forum.board', 'thunderclan-camp'))->assertOk()->assertSee('ThunderClan Camp');
    }

    public function test_map_artwork_uses_native_image_map_areas(): void
    {
        $map = $this->get(route('content.page', 'map'))->assertOk()
            ->assertSee('usemap="#territory-map"', false)
            ->assertSee('<map name="territory-map">', false)
            ->assertSee('shape="rect"', false)
            ->assertSee('data-coords="290,76,383,102"', false)
            ->assertSee('data-coords="242,631,350,664"', false)
            ->assertSee('data-coords="657,347,754,385"', false)
            ->assertSee('data-coords="250,102,354,140"', false)
            ->assertSee('data-coords="124,697,253,732"', false)
            ->assertSee(route('forum.board', 'thunderclan-camp'))
            ->assertSee(route('forum.board', 'moose-island'))
            ->assertSee(route('forum.board', 'mouse-island'));

        $this->assertSame(7, substr_count($map->getContent(), 'alt="Twoleg Path"'));
        $this->assertSame(32, substr_count($map->getContent(), '<area shape="rect"'));

        $areas = collect(TerritoryMap::locations())->flatMap(fn ($location) => collect($location['areas'])->map(fn ($bounds) => ['slug' => $location['slug'], 'bounds' => $bounds]))->values();
        foreach ($areas as $index => $area) {
            foreach ($areas->slice($index + 1) as $other) {
                if ($area['slug'] === $other['slug']) {
                    continue;
                }

                [$left, $top, $right, $bottom] = $area['bounds'];
                [$otherLeft, $otherTop, $otherRight, $otherBottom] = $other['bounds'];
                $this->assertTrue(
                    min($right, $otherRight) <= max($left, $otherLeft) || min($bottom, $otherBottom) <= max($top, $otherTop),
                    "{$area['slug']} and {$other['slug']} image areas overlap."
                );
            }
        }
    }

    public function test_fresh_forum_seed_does_not_restore_the_living_map(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseMissing('forum_categories', ['name' => 'The living map']);
        $this->assertDatabaseMissing('forum_boards', ['slug' => 'thunderclan-territory']);
        $this->assertDatabaseHas('forum_boards', ['slug' => 'thunderclan-camp']);
    }

    public function test_retiring_the_living_map_preserves_threads_in_camp_boards(): void
    {
        $this->seed(MapForumSeeder::class);
        $legacy = ForumCategory::create(['name' => 'The living map', 'sort_order' => 2]);
        $oldBoard = $legacy->boards()->create(['name' => 'ThunderClan territory', 'slug' => 'thunderclan-territory', 'is_ic' => true, 'sort_order' => 1]);
        $user = User::factory()->create(['status' => 'approved']);
        $thread = $oldBoard->threads()->create(['user_id' => $user->id, 'title' => 'An old story', 'slug' => 'an-old-story']);
        $post = $thread->posts()->create(['user_id' => $user->id, 'body' => 'A story that should be preserved.', 'is_ic' => true]);

        (require database_path('migrations/2026_09_30_000003_remove_legacy_living_map_forums.php'))->up();

        $this->assertDatabaseMissing('forum_categories', ['name' => 'The living map']);
        $this->assertDatabaseMissing('forum_boards', ['slug' => 'thunderclan-territory']);
        $this->assertDatabaseHas('forum_threads', ['id' => $thread->id, 'forum_board_id' => ForumBoard::where('slug', 'thunderclan-camp')->firstOrFail()->id]);
        $this->assertDatabaseHas('forum_posts', ['id' => $post->id, 'forum_thread_id' => $thread->id]);
    }

    public function test_missing_and_protected_pages_keep_site_shell_and_inline_error(): void
    {
        $this->get('/not-a-page')->assertNotFound()
            ->assertSee('class="page-error"', false)->assertSee('Return home');

        $member = User::factory()->create(['role' => 'registered', 'status' => 'approved']);
        $this->actingAs($member)->get(route('admin.world.index'))->assertForbidden()
            ->assertSee('class="page-error"', false)->assertDontSee('World pages');
    }

    public function test_guide_has_a_separate_article_below_the_shared_heading(): void
    {
        $this->get(route('content.page', 'guide'))->assertOk()
            ->assertSee('class="guide-article"', false)
            ->assertSee('class="page-heading page-heading-theme"', false)
            ->assertSee('<table>', false)
            ->assertSee('Make a character');
    }

    public function test_clan_and_outsider_indexes_link_to_separate_articles(): void
    {
        $this->get(route('content.page', 'clans'))->assertOk()
            ->assertSee('class="world-index-content"', false)
            ->assertSee(route('world.show', ['kind' => 'clans', 'slug' => 'thunderclan']))
            ->assertSee('RiverClan')->assertSee('ShadowClan')->assertSee('WindClan');
        $this->get(route('content.page', 'outsiders'))->assertOk()
            ->assertSee('class="world-index-content"', false)
            ->assertSee(route('world.show', ['kind' => 'outsiders', 'slug' => 'kittypets']))
            ->assertSee('Loners')->assertSee('Rogues');
        $this->get(route('world.show', ['kind' => 'clans', 'slug' => 'thunderclan']))->assertOk()
            ->assertSee('class="guide-article"', false);
        $this->get(route('world.show', ['kind' => 'outsiders', 'slug' => 'kittypets']))->assertOk()
            ->assertSee('class="guide-article"', false);
        $this->get(route('world.show', ['kind' => 'clans', 'slug' => 'kittypets']))->assertNotFound();
    }

    public function test_world_sidebar_links_to_clans_and_outsiders_including_added_pages(): void
    {
        $this->get(route('home'))->assertOk()
            ->assertSee(route('world.show', ['kind' => 'clans', 'slug' => 'thunderclan']))
            ->assertSee(route('world.show', ['kind' => 'outsiders', 'slug' => 'kittypets']));

        WorldPage::create([
            'kind' => 'outsiders', 'slug' => 'travellers', 'name' => 'Travellers', 'summary' => 'A life on the road.',
            'eyebrow' => 'Beyond the clans', 'title' => 'Travellers', 'intro' => 'Explore.', 'body' => 'Their story.', 'sort_order' => 5,
        ]);

        $this->get(route('home'))->assertOk()
            ->assertSee(route('world.show', ['kind' => 'outsiders', 'slug' => 'travellers']));
    }

    public function test_admin_can_create_edit_and_remove_world_pages(): void
    {
        $member = User::factory()->create(['role' => 'registered', 'status' => 'approved']);
        $this->actingAs($member)->get(route('admin.world.index'))->assertForbidden();
        $this->actingAs($member)->post(route('admin.world.store'), ['kind' => 'outsiders'])->assertForbidden();

        $admin = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        $this->actingAs($admin)->get(route('admin.world.index'))->assertOk()->assertSee('Kittypets');
        $this->actingAs($admin)->get(route('admin.world.create', ['kind' => 'outsiders']))->assertOk()->assertSee('data-article-editor', false);
        $this->actingAs($admin)->post(route('admin.world.store'), [
            'kind' => 'outsiders', 'slug' => 'travellers', 'name' => 'Travellers', 'summary' => 'A wandering life.',
            'eyebrow' => 'Beyond the clans', 'title' => 'Travellers', 'intro' => 'Discover their paths.',
            'body' => "## Their story\n\nPaths cross here.", 'sort_order' => 5,
        ])->assertRedirect();

        $page = WorldPage::where('slug', 'travellers')->firstOrFail();
        $this->get(route('content.page', 'outsiders'))->assertOk()->assertSee('A wandering life.');
        $this->actingAs($admin)->patch(route('admin.world.update', $page), [
            'name' => 'Travelling cats', 'summary' => 'A changing road.', 'eyebrow' => 'Beyond the clans',
            'title' => 'Travelling cats', 'intro' => 'Discover their paths.',
            'body' => "## Their story\n\n![Trail](/storage/world/trail.png)\n\n<script>alert('unsafe')</script>", 'sort_order' => 5,
        ])->assertRedirect();
        $this->get(route('world.show', ['kind' => 'outsiders', 'slug' => 'travellers']))->assertOk()
            ->assertSee('Travelling cats')->assertSee('src="/storage/world/trail.png"', false)
            ->assertDontSee('<script>', false);
        $this->get(route('content.page', 'outsiders'))->assertOk()->assertSee('A changing road.');

        $this->actingAs($admin)->delete(route('admin.world.destroy', $page))->assertRedirect();
        $this->get(route('world.show', ['kind' => 'outsiders', 'slug' => 'travellers']))->assertNotFound();
    }

    public function test_world_pages_validate_kind_slug_and_image_uploads(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        $this->actingAs($admin)->get(route('admin.world.create', ['kind' => 'invalid']))->assertNotFound();
        $this->actingAs($admin)->post(route('admin.world.store'), [
            'kind' => 'clans', 'slug' => 'thunderclan', 'name' => 'Duplicate', 'summary' => 'Summary',
            'eyebrow' => 'Clans', 'title' => 'Duplicate', 'intro' => 'Intro', 'body' => 'Body', 'sort_order' => 0,
        ])->assertSessionHasErrors('slug');

        $member = User::factory()->create(['role' => 'registered', 'status' => 'approved']);
        $this->actingAs($member)->post(route('admin.world.images.store'), ['image' => UploadedFile::fake()->image('map.png')])->assertForbidden();
        $response = $this->actingAs($admin)->post(route('admin.world.images.store'), ['image' => UploadedFile::fake()->image('map.png')]);
        $response->assertOk()->assertJsonStructure(['url']);
        Storage::disk('public')->assertExists('world/'.basename($response->json('url')));
        $this->actingAs($admin)->post(route('admin.world.images.store'), ['image' => UploadedFile::fake()->create('unsafe.svg', 1, 'image/svg+xml')])->assertSessionHasErrors('image');
    }

    public function test_admin_can_edit_guide_and_markdown_is_rendered_safely(): void
    {
        $member = User::factory()->create(['role' => 'registered', 'status' => 'approved']);
        $this->actingAs($member)->get(route('admin.guide.edit'))->assertForbidden();
        $this->actingAs($member)->patch(route('admin.guide.update'), ['eyebrow' => 'Changed'])->assertForbidden();

        $admin = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        $this->actingAs($admin)->get(route('admin.guide.edit'))->assertOk()->assertSee('guide-editor');
        $this->actingAs($admin)->patch(route('admin.guide.update'), [
            'eyebrow' => 'Start here',
            'title' => "The field\nguide.",
            'intro' => 'Learn about the world.',
            'body' => "## Paths\n\n| Clan | Home |\n| --- | --- |\n| RiverClan | Water |\n\n![Territory](/storage/guide/map.png)\n\n<script>alert('unsafe')</script>",
        ])->assertRedirect();

        $this->get(route('content.page', 'guide'))->assertOk()
            ->assertSee('Start here')->assertSee('The field')->assertSee('<h2>Paths</h2>', false)
            ->assertSee('<table>', false)->assertSee('src="/storage/guide/map.png"', false)
            ->assertDontSee('<script>', false);
        $this->assertDatabaseHas('content_pages', ['slug' => 'guide', 'eyebrow' => 'Start here']);
    }

    public function test_only_admins_can_upload_guide_images(): void
    {
        Storage::fake('public');
        $member = User::factory()->create(['role' => 'registered', 'status' => 'approved']);
        $this->actingAs($member)->post(route('admin.guide.images.store'), ['image' => UploadedFile::fake()->image('map.png')])->assertForbidden();

        $admin = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        $response = $this->actingAs($admin)->post(route('admin.guide.images.store'), ['image' => UploadedFile::fake()->image('map.png')]);
        $response->assertOk()->assertJsonStructure(['url']);
        Storage::disk('public')->assertExists('guide/'.basename($response->json('url')));

        $this->actingAs($admin)->post(route('admin.guide.images.store'), ['image' => UploadedFile::fake()->create('unsafe.svg', 1, 'image/svg+xml')])->assertSessionHasErrors('image');
    }

    public function test_rules_are_grouped_in_category_and_rule_order(): void
    {
        $category = RuleCategory::create(['name' => 'Conduct', 'sort_order' => 10]);
        Rule::create(['rule_category_id' => $category->id, 'title' => 'Second rule', 'description' => 'Second description', 'sort_order' => 2]);
        Rule::create(['rule_category_id' => $category->id, 'title' => 'First rule', 'description' => 'First description', 'sort_order' => 1]);

        $this->get(route('content.page', 'rules'))->assertOk()
            ->assertSeeInOrder(['Community', 'Storytelling', 'Conduct', 'First rule', 'First description', 'Second rule'])
            ->assertSee('start="1"', false)
            ->assertSee('start="2"', false)
            ->assertSee('start="3"', false)
            ->assertDontSee('class="section-number"', false);
    }

    public function test_only_admins_can_manage_rules_and_changes_appear_publicly(): void
    {
        $member = User::factory()->create(['role' => 'registered', 'status' => 'approved']);
        $this->actingAs($member)->get(route('admin.rules.index'))->assertForbidden();
        $this->actingAs($member)->post(route('admin.rules.categories.store'), ['name' => 'Conduct', 'sort_order' => 2])->assertForbidden();

        $admin = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        $this->actingAs($admin)->get(route('admin.rules.index'))->assertOk()->assertSee('New category');
        $this->actingAs($admin)->post(route('admin.rules.categories.store'), ['name' => 'Conduct', 'sort_order' => 2])->assertRedirect();
        $category = RuleCategory::where('name', 'Conduct')->firstOrFail();
        $this->actingAs($admin)->post(route('admin.rules.entries.store'), ['rule_category_id' => $category->id, 'title' => 'Be kind', 'description' => 'Respect fellow writers.', 'sort_order' => 0])->assertRedirect();
        $rule = Rule::where('title', 'Be kind')->firstOrFail();
        $this->actingAs($admin)->patch(route('admin.rules.entries.update', $rule), ['rule_category_id' => $category->id, 'title' => 'Be considerate', 'description' => 'Respect fellow writers.', 'sort_order' => 0])->assertRedirect();
        $this->get(route('content.page', 'rules'))->assertOk()->assertSee('Be considerate')->assertDontSee('Be kind');

        $this->actingAs($admin)->delete(route('admin.rules.categories.destroy', $category))->assertRedirect();
        $this->assertDatabaseMissing('rules', ['id' => $rule->id]);
    }

    public function test_forum_categories_boards_and_modules_each_have_their_own_admin_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'approved']);

        $this->actingAs($admin)->get(route('admin.content.categories'))->assertOk()->assertSee('New category')->assertDontSee('New sidebar module');
        $this->actingAs($admin)->get(route('admin.content.boards'))->assertOk()->assertSee('New board')->assertDontSee('New category');
        $this->actingAs($admin)->get(route('admin.content.modules'))->assertOk()->assertSee('New sidebar module')->assertDontSee('New board');
    }

    public function test_only_admins_can_edit_privacy_and_contact_pages_with_the_wysiwyg_editor(): void
    {
        $member = User::factory()->create(['role' => 'registered', 'status' => 'approved']);
        $this->actingAs($member)->get(route('admin.pages.edit', 'privacy'))->assertForbidden();

        $admin = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        $this->actingAs($admin)->get(route('admin.pages.edit', 'privacy'))->assertOk()->assertSee('data-article-editor', false);
        $this->actingAs($admin)->patch(route('admin.pages.update', 'privacy'), [
            'eyebrow' => 'Your information',
            'title' => 'Updated privacy title',
            'intro' => 'Updated intro copy.',
            'body' => 'Updated privacy body copy.',
        ])->assertRedirect();

        $this->get(route('content.page', 'privacy'))->assertOk()->assertSee('Updated privacy body copy.');
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
            ->assertSee('Time freeze');

        $item = ShopItem::create(['name' => 'Trail pass', 'slug' => 'trail-pass', 'description' => 'Access the distant paths.', 'cost' => 500, 'effect' => 'Outsider access']);
        $user = User::factory()->create(['status' => 'approved']);

        $this->actingAs($user)->get(route('shop'))->assertOk()
            ->assertSee('class="shop-card"', false)
            ->assertSee('Trail pass')
            ->assertSee('500')
            ->assertSee(route('shop.purchase', $item));
    }
}
