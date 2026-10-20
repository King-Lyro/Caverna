<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\SidebarModule;
use App\Models\SiteTheme;
use App\Models\SiteThemeSchedule;
use App\Models\User;
use App\Services\ThemeCalendar;
use App\Support\SitePresentation;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminThemesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'approved']);
    }

    private function theme(array $presentation = []): SiteTheme
    {
        return SiteTheme::create(['key' => 'event', 'name' => 'Event', 'colors' => SitePresentation::palettes()['winter'], 'presentation' => $presentation]);
    }

    private function fields(string $group, array $overrides): array
    {
        return collect(SitePresentation::fields($group))->map(function ($field, $key) use ($overrides) {
            return ['key' => $key] + ($overrides[$key] ?? ['mode' => 'inherit']);
        })->values()->all();
    }

    public function test_admin_can_manage_themes_and_all_editor_areas_render(): void
    {
        $this->actingAs($this->admin())->get(route('admin.themes.index'))->assertOk()->assertSee('Themes & appearance', false);
        $this->post(route('admin.themes.store'), ['name' => 'Halloween'])->assertRedirect();
        $theme = SiteTheme::where('name', 'Halloween')->firstOrFail();
        foreach (array_keys(SitePresentation::groups()) as $group) {
            $this->get(route('admin.themes.edit', [$theme, 'group' => $group]))->assertOk();
        }
        $this->patch(route('admin.themes.update', $theme), ['name' => 'Spooky', 'colors' => SitePresentation::palettes()['winter']])->assertRedirect();
        $this->assertSame('Spooky', $theme->fresh()->name);
        $this->post(route('admin.themes.duplicate', $theme))->assertRedirect();
        $this->assertDatabaseHas('site_themes', ['name' => 'Spooky (copy)', 'is_builtin' => false]);
        $this->delete(route('admin.themes.destroy', $theme))->assertRedirect();
        $this->assertModelMissing($theme);
        $this->assertDatabaseHas('audit_logs', ['action' => 'theme.created']);
    }

    public function test_guests_members_and_non_admin_staff_cannot_manage_or_preview_themes(): void
    {
        $theme = $this->theme();
        $this->get(route('admin.themes.index'))->assertRedirect(route('login'));
        foreach (['registered', 'moderator'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role, 'status' => 'approved']));
            $this->get(route('admin.themes.index'))->assertForbidden();
            $this->get(route('admin.themes.edit', $theme))->assertForbidden();
            $this->post(route('admin.themes.preview', $theme))->assertForbidden();
            $this->post(route('admin.themes.images'))->assertForbidden();
            $this->patch(route('admin.appearance.update'))->assertForbidden();
            $this->post(route('admin.themes.schedules.store'))->assertForbidden();
        }
    }

    public function test_creating_from_a_theme_copies_its_appearance_without_publishing_or_copying_dates(): void
    {
        $source = $this->theme(['header.season' => 'A festival', 'home.after' => []]);
        SiteThemeSchedule::create(['name' => 'Festival', 'site_theme_id' => $source->id, 'recurrence' => 'annual', 'starts_md' => '12-20', 'ends_md' => '01-05', 'is_enabled' => true]);
        $this->actingAs($this->admin())->post(route('admin.themes.store'), ['name' => 'Next festival', 'source_theme_id' => $source->id])->assertSessionHasNoErrors()->assertRedirect();
        $copy = SiteTheme::where('name', 'Next festival')->firstOrFail();
        $this->assertSame($source->colors, $copy->colors);
        $this->assertSame($source->presentation, $copy->presentation);
        $this->assertFalse($copy->is_builtin);
        $this->assertSame(0, $copy->schedules()->count());
        $this->assertDatabaseHas('site_settings', ['key' => 'theme', 'value' => 'fall']);
        $this->patch(route('admin.themes.update', $copy), ['name' => 'Renamed copy', 'colors' => SitePresentation::palettes()['spring']])->assertSessionHasNoErrors();
        $this->assertSame('Event', $source->fresh()->name);
        $this->post(route('admin.themes.store'), ['name' => 'Invalid', 'source_theme_id' => 999999])->assertSessionHasErrors('source_theme_id');
        $this->assertDatabaseMissing('site_themes', ['name' => 'Invalid']);
    }

    public function test_editor_sections_include_every_field_exactly_once_with_original_indexes(): void
    {
        SidebarModule::create(['title' => 'News', 'body' => 'Updates', 'placement' => 'left', 'is_enabled' => true, 'sort_order' => 0]);
        $theme = $this->theme();
        $this->actingAs($this->admin());
        foreach (array_keys(SitePresentation::groups()) as $group) {
            $fields = SitePresentation::fields($group);
            $sectionKeys = array_merge(...array_column(SitePresentation::editorSections($group), 'keys'));
            $this->assertCount(count($fields), $sectionKeys, $group);
            $this->assertEqualsCanonicalizing(array_keys($fields), $sectionKeys, $group);
            $response = $this->get(route('admin.themes.edit', [$theme, 'group' => $group]))->assertOk();
            foreach (array_keys($fields) as $index => $key) {
                $response->assertSee('name="fields['.$index.'][key]" value="'.$key.'"', false);
            }
        }
    }

    public function test_management_tabs_colors_and_live_notices_are_clear_even_in_preview(): void
    {
        $theme = $this->theme();
        $this->actingAs($this->admin());
        $this->get(route('admin.themes.index'))->assertOk()->assertSee('On your site now')->assertSee('Create & start editing', false)->assertDontSee('Add event dates');
        $this->get(route('admin.themes.index', ['tab' => 'schedules']))->assertOk()->assertSee('Add event dates')->assertSee('First day (month-day)')->assertDontSee('Your theme collection');
        $this->get(route('admin.themes.index', ['tab' => 'defaults']))->assertOk()->assertSee('Edit site-wide defaults')->assertSee('Looking for page content?');
        $this->get(route('admin.themes.index', ['tab' => 'invalid']))->assertNotFound();
        $this->get(route('admin.themes.edit', [$theme, 'group' => 'colors']))->assertOk()->assertSee('Everyday colors')->assertSee('Main color')->assertSee('data-theme-colors', false);
        $this->get(route('admin.appearance.edit', ['group' => 'colors']))->assertNotFound();
        $this->post(route('admin.themes.preview', $theme));
        $this->get(route('admin.themes.edit', $theme))->assertSee('You can prepare this theme privately.')->assertDontSee('This theme is live.');
        $fall = SiteTheme::where('key', 'fall')->firstOrFail();
        $this->get(route('admin.themes.edit', $fall))->assertSee('This theme is live.');
        DB::table('site_settings')->where('key', 'theme')->update(['value' => 'missing-theme']);
        $this->get(route('admin.themes.edit', $fall))->assertSee('This theme is live.');
        $this->get(route('admin.themes.index'))->assertSee('Edit current theme');
    }

    public function test_save_and_preview_persists_before_previewing_without_activation(): void
    {
        $theme = $this->theme();
        $this->actingAs($this->admin())->patch(route('admin.themes.presentation', $theme), [
            'form_complete' => 1, 'group' => 'header', 'preview' => 1,
            'fields' => $this->fields('header', ['header.season' => ['mode' => 'custom', 'value' => 'Saved preview message']]),
        ])->assertSessionHasNoErrors()->assertRedirect(route('home'))->assertSessionHas('theme_preview', $theme->key);
        $this->assertSame('Saved preview message', $theme->fresh()->presentation['header.season']);
        $this->get(route('home'))->assertSee('Saved preview message');
        $this->assertDatabaseHas('site_settings', ['key' => 'theme', 'value' => 'fall']);
        $this->patch(route('admin.themes.update', $theme), ['name' => 'Preview colors', 'colors' => SitePresentation::palettes()['spring'], 'preview' => 1])
            ->assertSessionHasNoErrors()->assertRedirect(route('home'));
        $this->assertSame('Preview colors', $theme->fresh()->name);
        $this->assertSame(SitePresentation::palettes()['spring'], $theme->fresh()->colors);
        $this->withSession(['theme_preview' => null])->patch(route('admin.themes.update', $theme), ['name' => '', 'colors' => [], 'preview' => 1])
            ->assertSessionHasErrors()->assertSessionMissing('theme_preview');
    }

    public function test_custom_controls_are_only_visible_for_custom_fields(): void
    {
        $theme = $this->theme(['header.tagline' => 'Custom tagline', 'header.season' => '']);
        $this->actingAs($this->admin());
        $response = $this->get(route('admin.themes.edit', $theme))->assertOk()
            ->assertSee('Active theme')->assertDontSee('Usual theme')
            ->assertDontSee('Changed here')->assertDontSee('<label>Setting', false)
            ->assertSee('aria-label="Season or event message option"', false);
        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        foreach (['header.image' => 'inherit', 'header.tagline' => 'custom', 'header.season' => 'hidden'] as $key => $mode) {
            $field = '//div[@data-theme-field][input[@value="'.$key.'"]]';
            $this->assertSame($mode, $xpath->query($field.'//select[@data-theme-mode]/option[@selected]')->item(0)->getAttribute('value'));
            $this->assertFalse($xpath->query($field.'/div[@data-field-content]')->item(0)->hasAttribute('hidden'));
            $this->assertFalse($xpath->query($field.'/div[@class="theme-field-settings"]')->item(0)->hasAttribute('hidden'));
        }
        $response = $this->withSession(['_old_input' => ['fields' => [0 => ['mode' => 'custom']]]])
            ->get(route('admin.themes.edit', $theme))->assertOk();
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $this->assertFalse($xpath->query('//div[@data-theme-field][input[@value="header.image"]]/div[@data-field-content]')->item(0)->hasAttribute('hidden'));
    }

    public function test_theme_overview_headings_have_no_preceding_eyebrow_paragraphs(): void
    {
        $this->actingAs($this->admin());
        foreach (['themes', 'schedules', 'defaults'] as $tab) {
            $response = $this->get(route('admin.themes.index', ['tab' => $tab]))->assertOk()
                ->assertDontSee('Usual theme')->assertDontSee('usual theme');
            $document = new \DOMDocument;
            @$document->loadHTML($response->getContent());
            $xpath = new \DOMXPath($document);
            $this->assertSame(0, $xpath->query('//div[@class="theme-workspace"]//h2/preceding-sibling::*[1][self::p]')->length);
        }
    }

    public function test_preview_actions_open_in_a_new_tab_without_changing_the_save_target(): void
    {
        $theme = $this->theme();
        $this->actingAs($this->admin());
        foreach ([route('admin.themes.index'), route('admin.themes.edit', $theme), route('admin.themes.edit', [$theme, 'group' => 'colors'])] as $url) {
            $response = $this->get($url)->assertOk();
            $document = new \DOMDocument;
            @$document->loadHTML($response->getContent());
            $xpath = new \DOMXPath($document);
            $previewForms = $xpath->query('//form[contains(@action, "/preview")]');
            $this->assertGreaterThan(0, $previewForms->length);
            foreach ($previewForms as $form) {
                $this->assertSame('_blank', $form->getAttribute('target'));
                $this->assertSame('noopener', $form->getAttribute('rel'));
            }
            foreach ($xpath->query('//button[@data-preview-new-tab]') as $button) {
                $this->assertSame('_blank', $button->getAttribute('formtarget'));
                $this->assertSame('preview', $button->getAttribute('name'));
                $this->assertSame(0, $xpath->query('ancestor::form[@target]', $button)->length);
            }
        }
    }

    public function test_base_inheritance_theme_overrides_hidden_values_and_reset(): void
    {
        $theme = $this->theme();
        $this->actingAs($this->admin());
        $this->patch(route('admin.appearance.update'), ['form_complete' => 1, 'group' => 'header', 'fields' => $this->fields('header', [
            'header.season' => ['mode' => 'custom', 'value' => 'A shared season'],
        ])])->assertSessionHasNoErrors();
        $this->post(route('theme.update', $theme->key));
        $this->get(route('home'))->assertSee('A shared season');
        $this->patch(route('admin.themes.presentation', $theme), ['form_complete' => 1, 'group' => 'header', 'fields' => $this->fields('header', [
            'header.season' => ['mode' => 'custom', 'value' => 'An event season'],
        ])])->assertSessionHasNoErrors();
        $this->get(route('home'))->assertSee('An event season')->assertDontSee('A shared season');
        $this->patch(route('admin.themes.presentation', $theme), ['form_complete' => 1, 'group' => 'header', 'fields' => $this->fields('header', [
            'header.season' => ['mode' => 'hidden'],
        ])])->assertSessionHasNoErrors();
        $this->get(route('home'))->assertDontSee('A shared season')->assertDontSee('An event season');
        $this->patch(route('admin.themes.presentation', $theme), ['form_complete' => 1, 'group' => 'header', 'fields' => $this->fields('header', [])])->assertSessionHasNoErrors();
        $this->get(route('home'))->assertSee('A shared season');
    }

    public function test_preview_is_admin_session_only_and_dashboard_member_link_is_preserved(): void
    {
        $theme = $this->theme(['home.hero_title' => 'Preview artwork event', 'page.dashboard.title' => 'Hello,']);
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.themes.preview', $theme))->assertRedirect(route('home'));
        $this->get(route('home'))->assertSee('Preview artwork event')->assertSee('Administrator preview');
        $this->get(route('dashboard'))->assertSee('Hello,')->assertSee('href="'.route('members.show', $admin).'"', false);
        $this->actingAs(User::factory()->create(['status' => 'approved']))->get(route('home'))->assertDontSee('Preview artwork event');
        $this->actingAs($admin)->post(route('admin.themes.preview.stop'));
        $this->get(route('home'))->assertDontSee('Preview artwork event');
        $this->assertDatabaseHas('site_settings', ['key' => 'theme', 'value' => 'fall']);
    }

    public function test_scheduled_boundaries_pause_and_return_to_manual_base_ignore_forever_cache(): void
    {
        config(['app.timezone' => 'America/New_York']);
        $theme = $this->theme();
        SiteThemeSchedule::create(['name' => 'Event', 'site_theme_id' => $theme->id, 'recurrence' => 'once', 'starts_on' => '2026-12-20', 'ends_on' => '2026-12-21', 'is_enabled' => true]);
        Cache::forever('site_theme', 'summer');
        $this->travelTo(CarbonImmutable::parse('2026-12-20 04:59:59', 'UTC'));
        $this->get(route('home'))->assertSee('theme-fall');
        $this->travelTo(CarbonImmutable::parse('2026-12-20 05:00:00', 'UTC'));
        $this->get(route('home'))->assertSee('theme-event');
        $this->actingAs($this->admin())->post(route('theme.update', 'spring'));
        $this->get(route('home'))->assertSee('theme-event');
        $this->patch(route('admin.themes.scheduling'), ['enabled' => false])->assertSessionHasNoErrors();
        $this->get(route('home'))->assertSee('theme-spring');
        $this->patch(route('admin.themes.scheduling'), ['enabled' => true]);
        $this->travelTo(CarbonImmutable::parse('2026-12-22 04:59:59', 'UTC'));
        $this->get(route('home'))->assertSee('theme-event');
        $this->travelTo(CarbonImmutable::parse('2026-12-22 05:00:00', 'UTC'));
        $this->get(route('home'))->assertSee('theme-spring');
    }

    public function test_annual_leap_day_new_year_and_overlap_validation(): void
    {
        $theme = $this->theme();
        $this->actingAs($this->admin());
        $annual = ['site_theme_id' => $theme->id, 'name' => 'Holiday', 'recurrence' => 'annual', 'starts_md' => '12-20', 'ends_md' => '01-05', 'is_enabled' => true];
        $this->post(route('admin.themes.schedules.store'), $annual)->assertSessionHasNoErrors();
        $this->post(route('admin.themes.schedules.store'), [
            'site_theme_id' => $theme->id, 'name' => 'Future collision', 'recurrence' => 'once', 'starts_on' => '2040-01-05', 'ends_on' => '2040-01-06', 'is_enabled' => true,
        ])->assertSessionHasErrors();
        $this->post(route('admin.themes.schedules.store'), array_replace($annual, ['name' => 'Other annual', 'starts_md' => '01-05', 'ends_md' => '01-06']))->assertSessionHasErrors();
        $this->post(route('admin.themes.schedules.store'), array_replace($annual, ['starts_md' => '02-29', 'ends_md' => '02-29']))->assertSessionHasNoErrors();
        $calendar = new ThemeCalendar;
        $leap = new SiteThemeSchedule(['recurrence' => 'annual', 'starts_md' => '02-29', 'ends_md' => '02-29']);
        $this->assertTrue($calendar->matches($leap, CarbonImmutable::parse('2027-02-28')));
        $this->assertFalse($calendar->matches($leap, CarbonImmutable::parse('2028-02-28')));
        $this->assertTrue($calendar->matches($leap, CarbonImmutable::parse('2028-02-29')));
        $this->assertTrue($calendar->matches(new SiteThemeSchedule($annual), CarbonImmutable::parse('2027-01-05')));
        $this->assertFalse($calendar->matches(new SiteThemeSchedule($annual), CarbonImmutable::parse('2027-01-06')));
        $this->post(route('admin.themes.schedules.store'), array_replace($annual, ['starts_md' => '02-30']))->assertSessionHasErrors();
    }

    public function test_image_upload_map_restrictions_and_shared_asset_retention(): void
    {
        Storage::fake('public');
        $theme = $this->theme();
        $this->actingAs($this->admin());
        $this->post(route('admin.themes.images'), ['image' => UploadedFile::fake()->create('bad.svg', 2, 'image/svg+xml')])->assertSessionHasErrors();
        $response = $this->post(route('admin.themes.images'), ['image' => UploadedFile::fake()->image('banner.png', 1200, 300)])->assertOk();
        $path = $response->json('path');
        Storage::disk('public')->assertExists($path);
        $theme->update(['presentation' => ['header.image' => $path]]);
        $this->post(route('admin.themes.duplicate', $theme));
        $this->delete(route('admin.themes.destroy', $theme))->assertSessionHasNoErrors();
        Storage::disk('public')->assertExists($path);
        $copy = SiteTheme::where('name', 'Event (copy)')->firstOrFail();
        $fields = $this->fields('page:content.map', ['map.image' => ['mode' => 'custom', 'image' => UploadedFile::fake()->image('map.png', 500, 500)]]);
        $this->patch(route('admin.themes.presentation', $copy), ['form_complete' => 1, 'group' => 'page:content.map', 'fields' => $fields, 'map_confirm' => 1])->assertSessionHasErrors();
        $fields = $this->fields('page:content.map', ['map.image' => ['mode' => 'custom', 'image' => UploadedFile::fake()->image('map.png', 781, 781)]]);
        $this->patch(route('admin.themes.presentation', $copy), ['form_complete' => 1, 'group' => 'page:content.map', 'fields' => $fields])->assertSessionHasErrors();
        $this->patch(route('admin.themes.presentation', $copy), ['form_complete' => 1, 'group' => 'page:content.map', 'fields' => $fields, 'map_confirm' => 1])->assertSessionHasNoErrors();
        $this->post(route('theme.update', $copy->key));
        $this->get(route('content.page', 'map'))->assertOk()->assertSee('usemap="#territory-map"', false)->assertSee(Storage::disk('public')->url($copy->fresh()->presentation['map.image']));
    }

    public function test_blocks_order_safe_content_and_protected_theme_deletion(): void
    {
        $theme = $this->theme();
        $this->actingAs($this->admin());
        $blocks = [['title' => 'First box', 'body' => '<script>alert(1)</script>'], ['title' => 'Second box', 'link' => 'javascript:alert(1)']];
        $this->patch(route('admin.themes.presentation', $theme), ['form_complete' => 1, 'group' => 'home', 'fields' => $this->fields('home', [
            'home.after' => ['mode' => 'custom', 'blocks' => $blocks],
        ])])->assertSessionHasErrors();
        $blocks[1]['link'] = '/guide';
        $this->patch(route('admin.themes.presentation', $theme), ['form_complete' => 1, 'group' => 'home', 'fields' => $this->fields('home', [
            'home.after' => ['mode' => 'custom', 'blocks' => $blocks],
        ])])->assertSessionHasNoErrors();
        $this->post(route('theme.update', $theme->key));
        $this->get(route('home'))->assertSeeInOrder(['First box', 'Second box'])->assertDontSee('<script>alert(1)</script>', false);
        $this->delete(route('admin.themes.destroy', $theme))->assertSessionHasErrors();
        $this->delete(route('admin.themes.destroy', SiteTheme::where('key', 'fall')->first()))->assertSessionHasErrors();
        $this->patch(route('admin.themes.update', $theme), ['name' => 'Bad', 'colors' => ['accent' => 'red;display:none']])->assertSessionHasErrors();
    }

    public function test_all_requested_page_headings_artwork_and_regions_render_actual_overrides(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('themes/banner.png', 'fixture');
        $pages = [
            'forum' => route('forum'), 'characters' => route('characters'),
            'characters.memorial' => route('characters.memorial'), 'activity' => route('activity'),
            'breeding.create' => route('breeding.create'), 'shop' => route('shop'),
            'inventory' => route('inventory'), 'crickets' => route('crickets'),
            'adoption.index' => route('adoption.index'), 'sales.index' => route('sales.index'),
            'dashboard' => route('dashboard'), 'account.edit' => route('account.edit'),
            'notifications' => route('notifications'), 'characters.create' => route('characters.create'),
            'sales.create' => route('sales.create'), 'adoption.create' => route('adoption.create'),
            'staff.index' => route('staff.index'),
        ];
        foreach (['rules', 'guide', 'clans', 'map', 'outsiders', 'privacy', 'contact'] as $slug) {
            $pages['content.'.$slug] = route('content.page', $slug);
        }
        $pages['world.clans.thunderclan'] = route('world.show', ['kind' => 'clans', 'slug' => 'thunderclan']);
        $values = [];
        foreach ($pages as $key => $url) {
            $prefix = 'page.'.$key;
            $values[$prefix.'.eyebrow'] = 'Custom eyebrow '.$key;
            $values[$prefix.'.title'] = 'Custom title '.$key;
            $values[$prefix.'.intro'] = 'Custom intro '.$key;
            $values[$prefix.'.image'] = 'themes/banner.png';
            $values[$prefix.'.alt'] = 'Banner '.$key;
            $values[$prefix.'.before'] = [['title' => 'Before '.$key]];
            $values[$prefix.'.after'] = [['title' => 'After '.$key]];
        }
        $theme = $this->theme($values);
        $this->actingAs($this->admin())->post(route('theme.update', $theme->key));
        foreach ($pages as $key => $url) {
            $this->get($url)->assertOk()->assertSee('Custom eyebrow '.$key)->assertSee('Custom intro '.$key)
                ->assertSee('alt="Banner '.$key.'"', false)->assertSeeInOrder(['Before '.$key, 'Custom title '.$key, 'After '.$key]);
        }
        $this->get(route('content.page', 'guide'))->assertSee('Make a character');
        $this->get(route('content.page', 'clans'))->assertSee('ThunderClan');
    }

    public function test_home_sidebar_modules_buttons_and_box_removal_keep_live_data(): void
    {
        $module = SidebarModule::create(['title' => 'Shared module', 'body' => 'Original module body', 'placement' => 'left', 'sort_order' => 0, 'is_enabled' => true]);
        $theme = $this->theme([
            'header.season' => 'Autumn event', 'header.tagline' => 'New tagline',
            'home.hero_title' => 'Event hero', 'home.enter' => 'Start here', 'home.features' => [],
            'home.overview' => [['title' => 'Custom overview', 'body' => 'Event body', 'link' => '/guide', 'label' => 'Our guide']],
            'shell.four_paths' => 'Our clans', 'shell.at_glance' => 'Live figures',
            'module.'.$module->id.'.title' => 'Event module', 'module.'.$module->id.'.body' => '**Event module body**',
            'sidebar.stats.after' => [['title' => 'Below live counts']],
        ]);
        $this->actingAs($this->admin())->post(route('theme.update', 'fall'), ['theme' => $theme->key])->assertSessionHasNoErrors();
        $this->get(route('home'))->assertSee('Autumn event')->assertSee('New tagline')
            ->assertSee('Event hero')->assertSee('Start here')->assertSee('Our guide')
            ->assertSee('Our clans')->assertSee('ThunderClan')->assertSee('Live figures')
            ->assertSee('Open stories</dt><dd>0', false)->assertSee('Below live counts')
            ->assertSee('Event module')->assertSee('<strong>Event module body</strong>', false)
            ->assertDontSee('Give your first life a history.');
        $this->patch(route('admin.themes.presentation', $theme), ['form_complete' => 1, 'group' => 'sidebars', 'fields' => $this->fields('sidebars', [
            'module.'.$module->id.'.visible' => ['mode' => 'hidden'],
        ])])->assertSessionHasNoErrors();
        $this->get(route('home'))->assertDontSee('Event module body')->assertDontSee('Original module body');
    }

    public function test_disabled_schedules_adjacent_ranges_updates_deletion_and_selection_protection(): void
    {
        $theme = $this->theme();
        $this->actingAs($this->admin());
        $first = ['site_theme_id' => $theme->id, 'name' => 'One day', 'recurrence' => 'once', 'starts_on' => '2026-10-31', 'ends_on' => '2026-10-31', 'is_enabled' => true];
        $this->post(route('admin.themes.schedules.store'), $first)->assertSessionHasNoErrors();
        $schedule = SiteThemeSchedule::firstOrFail();
        $this->post(route('admin.themes.schedules.store'), array_replace($first, ['name' => 'Disabled collision', 'is_enabled' => false]))->assertSessionHasNoErrors();
        $disabled = SiteThemeSchedule::where('is_enabled', false)->firstOrFail();
        $this->patch(route('admin.themes.schedules.update', $disabled), array_replace($first, ['is_enabled' => true]))->assertSessionHasErrors();
        $this->post(route('admin.themes.schedules.store'), array_replace($first, ['name' => 'Next day', 'starts_on' => '2026-11-01', 'ends_on' => '2026-11-01']))->assertSessionHasNoErrors();
        $this->patch(route('admin.themes.schedules.update', $schedule), $first)->assertSessionHasNoErrors();
        $this->delete(route('admin.themes.destroy', $theme))->assertSessionHasErrors();
        foreach (SiteThemeSchedule::all() as $entry) {
            $this->delete(route('admin.themes.schedules.destroy', $entry))->assertSessionHasNoErrors();
        }
        $this->delete(route('admin.themes.destroy', $theme))->assertSessionHasNoErrors();
        $this->assertModelMissing($theme);
    }

    public function test_failed_presentation_save_rolls_back_changes_and_removes_new_artwork(): void
    {
        Storage::fake('public');
        $theme = $this->theme();
        $this->actingAs($this->admin())->withoutExceptionHandling();
        AuditLog::creating(fn () => throw new \RuntimeException('Audit persistence failed'));
        try {
            $this->patch(route('admin.themes.presentation', $theme), ['form_complete' => 1, 'group' => 'header', 'fields' => $this->fields('header', [
                'header.image' => ['mode' => 'custom', 'image' => UploadedFile::fake()->image('banner.png', 800, 200)],
                'header.season' => ['mode' => 'custom', 'value' => 'Must roll back'],
            ])]);
            $this->fail('The persistence error should propagate.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Audit persistence failed', $exception->getMessage());
        }
        $this->assertSame([], $theme->fresh()->presentation);
        $this->assertSame([], Storage::disk('public')->allFiles('themes'));
    }

    public function test_image_size_pixel_limits_unknown_fields_and_asset_paths_are_rejected(): void
    {
        Storage::fake('public');
        $theme = $this->theme();
        $this->actingAs($this->admin());
        $this->post(route('admin.themes.images'), ['image' => UploadedFile::fake()->image('wide.png', 6001, 1)])->assertSessionHasErrors();
        $this->post(route('admin.themes.images'), ['image' => UploadedFile::fake()->image('large.png')->size(5121)])->assertSessionHasErrors();
        $this->patch(route('admin.themes.presentation', $theme), ['form_complete' => 1, 'group' => 'header', 'fields' => $this->fields('header', [
            'header.image' => ['mode' => 'custom', 'value' => '../../private/secret.png'],
        ])])->assertSessionHasErrors();
        $fields = $this->fields('header', []);
        $fields[0]['unknown'] = 'Unexpected configuration';
        $this->patch(route('admin.themes.presentation', $theme), ['form_complete' => 1, 'group' => 'header', 'fields' => $fields])->assertSessionHasErrors();
        $fields = $this->fields('header', []);
        [$fields[0], $fields[1]] = [$fields[1], $fields[0]];
        $this->patch(route('admin.themes.presentation', $theme), ['form_complete' => 1, 'group' => 'header', 'fields' => $fields])->assertSessionHasErrors();
        $this->patch(route('admin.themes.presentation', $theme), ['group' => 'header', 'fields' => $this->fields('header', [])])->assertSessionHasErrors('form_complete');
        $this->post(route('admin.themes.store'), ['name' => str_repeat('Long theme ', 10)])->assertSessionHasNoErrors();
        $long = SiteTheme::orderByDesc('id')->first();
        $this->assertLessThanOrEqual(120, strlen($long->key));
        $this->post(route('theme.update', 'fall'), ['theme' => $long->key])->assertSessionHasNoErrors();
    }
}
