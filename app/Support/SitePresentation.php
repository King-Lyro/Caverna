<?php

namespace App\Support;

use App\Models\SidebarModule;
use App\Models\WorldPage;
use Illuminate\Support\Facades\Schema;

class SitePresentation
{
    public static function palettes(): array
    {
        $common = ['white' => '#ffffff', 'paper' => '#fbfcfb', 'ink' => '#23332c', 'muted' => '#73817b'];

        return [
            'spring' => $common + ['bg' => '#f3f8f4', 'accent' => '#47765a', 'deep' => '#294b39', 'soft' => '#e8f1eb', 'warm' => '#cf7d58', 'line' => '#d8e8dc'],
            'summer' => $common + ['bg' => '#fafbf3', 'accent' => '#798052', 'deep' => '#4d5938', 'soft' => '#f0f1e2', 'warm' => '#c88b42', 'line' => '#e7e6d4'],
            'fall' => $common + ['bg' => '#fcf7f1', 'accent' => '#865f3f', 'deep' => '#553828', 'soft' => '#f5e9de', 'warm' => '#bd6e42', 'line' => '#ecddd0'],
            'winter' => $common + ['bg' => '#f1f7fa', 'accent' => '#54778c', 'deep' => '#2d4b5c', 'soft' => '#e7f0f4', 'warm' => '#a66c5b', 'line' => '#d8e5eb'],
        ];
    }

    public static function pageKey(): string
    {
        if (request()->routeIs('content.page')) {
            return 'content.'.request()->route('page');
        }
        if (request()->routeIs('world.show')) {
            return 'world.'.request()->route('kind').'.'.request()->route('slug');
        }

        return request()->route()?->getName() ?? 'home';
    }

    public static function pages(): array
    {
        $pages = [
            'forum' => 'Forum', 'forum.board' => 'Forum board', 'forum.thread' => 'Forum thread',
            'forum.thread.create' => 'New forum thread', 'characters' => 'Character directory',
            'characters.memorial' => 'Memorial', 'characters.create' => 'Create character',
            'characters.edit' => 'Edit character', 'characters.show' => 'Character profile',
            'activity' => 'Activity', 'breeding.create' => 'Breeding', 'shop' => 'Shop',
            'inventory' => 'Inventory', 'crickets' => 'Cricket ledger', 'adoption.index' => 'Adoption',
            'adoption.create' => 'Create adoptable', 'adoption.show' => 'Adoptable details',
            'sales.index' => 'Character sales', 'sales.create' => 'List a character',
            'dashboard' => 'Dashboard', 'account.edit' => 'Account', 'notifications' => 'Notifications',
            'members.show' => 'Member profile', 'login' => 'Login', 'register' => 'Registration',
            'password.request' => 'Password recovery', 'password.reset' => 'Password reset',
            'staff.index' => 'Staff overview',
        ];
        foreach (['rules', 'guide', 'clans', 'map', 'outsiders', 'privacy', 'contact'] as $slug) {
            $pages['content.'.$slug] = ucfirst($slug);
        }
        if (Schema::hasTable('world_pages')) {
            foreach (WorldPage::all(['kind', 'slug', 'name']) as $page) {
                $pages['world.'.$page->kind.'.'.$page->slug] = $page->name;
            }
        }

        return $pages;
    }

    public static function groups(): array
    {
        return ['header' => 'Header & season', 'home' => 'Homepage', 'sidebars' => 'Sidebars']
            + collect(self::pages())->mapWithKeys(fn ($label, $key) => ['page:'.$key => $label.' page'])->all();
    }

    public static function editorSections(string $group): array
    {
        $fields = self::fields($group);
        $section = fn ($title, $description, $keys) => [
            'title' => $title, 'description' => $description,
            'keys' => array_values(array_intersect($keys, array_keys($fields))),
        ];
        if ($group === 'header') {
            return [
                $section('Header image', 'The wide artwork at the very top of every page, above the navigation menu.', ['header.image', 'header.alt', 'header.fit', 'header.position']),
                $section('Site title & season message', 'The site name, short tagline, and season or event message beside the header.', ['header.show_title', 'header.tagline', 'header.season']),
            ];
        }
        if ($group === 'home') {
            return [
                $section('Main welcome banner', 'The first large panel on the homepage: headline and buttons on the left, artwork on the right.', ['home.hero_kicker', 'home.hero_title', 'home.hero_intro', 'home.enter', 'home.visit_forum', 'home.image', 'home.alt', 'home.scene_caption']),
                $section('Welcome message', 'The text box below the main banner, beside the three getting-started links.', ['home.welcome.visible', 'home.welcome_eyebrow', 'home.welcome_title', 'home.welcome_body', 'home.welcome.image', 'home.welcome.alt']),
                $section('Getting-started links', 'The three cards: Build a character, Choose an allegiance, and Start a story.', ['home.features']),
                $section('Bottom information cards', 'The Meet the clans and Read the guide cards at the bottom of the homepage.', ['home.overview']),
                $section('Extra homepage content', 'Optional text or image boxes above or below the main welcome banner. Leave these empty if you do not need them.', ['home.before', 'home.after']),
            ];
        }
        if ($group === 'sidebars') {
            $sections = [];
            $groups = [
                'welcome' => ['Welcome card', 'Left sidebar: Welcome to Cavernas / Find your way in.', ['shell.begin_here', 'shell.find_way', 'shell.guide_intro', 'shell.read_guide']],
                'paths' => ['Clan & outsider links', 'Left sidebar: headings above the live clan and outsider navigation. Names and destinations stay unchanged.', ['shell.four_paths', 'shell.outsiders']],
                'chat' => ['Chatbox', 'Left sidebar: live chat below the clan and outsider links. Customize its heading and artwork or add content around it; messages and moderation stay unchanged.', ['shell.chat_title']],
                'about' => ['About the site', 'Right sidebar: About Cavernas / The gates are open.', ['shell.field_notes', 'shell.gates_open', 'shell.shell_intro', 'shell.view_rules']],
                'stats' => ['Live site statistics', 'Right sidebar: At a glance. You can change its heading and add artwork, but the numbers stay live.', ['shell.at_glance']],
            ];
            foreach ($groups as $key => [$title, $description, $keys]) {
                $sections[] = $section($title, $description, [...$keys, "sidebar.$key.image", "sidebar.$key.alt", "sidebar.$key.before", "sidebar.$key.after"]);
            }
            $modules = array_filter(array_keys($fields), fn ($key) => str_starts_with($key, 'module.'));
            if ($modules) {
                $sections[] = $section('Your additional sidebar cards', 'Cards created in Sidebar modules. Changes here apply only to this theme; their placement is managed in Sidebar modules.', $modules);
            }

            return $sections;
        }
        $text = $art = $boxes = [];
        foreach ($fields as $key => $field) {
            if ($field['type'] === 'blocks') {
                $boxes[] = $key;
            } elseif ($field['type'] === 'image' || str_ends_with($key, '.alt')) {
                $art[] = $key;
            } else {
                $text[] = $key;
            }
        }

        return array_values(array_filter([
            $section('Page title & introduction', 'The heading panel at the top of this page. Existing page-body editors and live information are not changed.', $text),
            $section('Page artwork', 'Artwork inside the page heading. This does not replace the site-wide header image.', $art),
            $section('Extra content boxes', 'Optional text, images, or links immediately above or below the page heading.', $boxes),
        ], fn ($item) => $item['keys']));
    }

    public static function fieldLabel(string $key, string $fallback): string
    {
        $labels = [
            'header.image' => 'Header image', 'header.alt' => 'Image description',
            'header.fit' => 'How should the image fit?', 'header.position' => 'Image alignment',
            'header.tagline' => 'Tagline below the site name', 'header.season' => 'Season or event message',
            'home.hero_kicker' => 'Small label above the headline', 'home.hero_title' => 'Main headline',
            'home.hero_intro' => 'Welcome paragraph', 'home.enter' => 'Join button text',
            'home.visit_forum' => 'Forum button text', 'home.scene_caption' => 'Artwork caption',
            'home.image' => 'Main banner image', 'home.alt' => 'Image description',
            'home.welcome_eyebrow' => 'Small label', 'home.welcome_title' => 'Welcome heading',
            'home.welcome_body' => 'Welcome paragraph', 'home.features' => 'Getting-started cards',
            'home.overview' => 'Information cards', 'home.before' => 'Above the main banner',
            'home.after' => 'Below the main banner', 'shell.begin_here' => 'Small label',
            'shell.find_way' => 'Welcome heading', 'shell.guide_intro' => 'Welcome paragraph',
            'shell.read_guide' => 'Guide link text', 'shell.four_paths' => 'Clan navigation heading',
            'shell.field_notes' => 'Small label', 'shell.gates_open' => 'About heading',
            'shell.shell_intro' => 'About paragraph', 'shell.view_rules' => 'Rules link text',
            'shell.at_glance' => 'Statistics heading', 'map.image' => 'Clickable territory map',
            'shell.chat_title' => 'Chatbox heading',
        ];

        return $labels[$key] ?? match (true) {
            str_ends_with($key, '.alt') => 'Image description',
            str_ends_with($key, '.eyebrow') => 'Small label above the title',
            $key === 'page.dashboard.title' => 'Dashboard greeting',
            str_ends_with($key, '.title') && str_starts_with($key, 'page.') => 'Page title',
            default => $fallback,
        };
    }

    public static function colorLabels(): array
    {
        return [
            'accent' => ['Main color', 'Page banners, links, and primary buttons'],
            'deep' => ['Dark accent', 'Banner edges and darker highlights'],
            'bg' => ['Page background', 'The background behind the site'],
            'white' => ['Card background', 'Content boxes, menus, and form fields'],
            'ink' => ['Main text', 'Headings and body text'],
            'muted' => ['Secondary text', 'Captions and supporting information'],
            'soft' => ['Soft highlight', 'Subtle panels and selected items'],
            'warm' => ['Extra accent', 'Small decorative highlights'],
            'line' => ['Borders', 'Lines around cards and fields'],
            'paper' => ['Secondary background', 'Quiet panels and supporting areas'],
        ];
    }

    public static function fields(string $group): array
    {
        $text = fn ($label, $default = null) => ['type' => 'text', 'label' => $label, 'default' => $default];
        $image = fn ($label) => ['type' => 'image', 'label' => $label, 'default' => null];
        $blocks = fn ($label) => ['type' => 'blocks', 'label' => $label, 'default' => []];
        if ($group === 'header') {
            return [
                'header.image' => $image('Header artwork (wide banner recommended)'),
                'header.alt' => $text('Header artwork alternative text'),
                'header.fit' => ['type' => 'choice', 'label' => 'Artwork fit', 'default' => 'contain', 'choices' => ['contain', 'cover']],
                'header.position' => ['type' => 'choice', 'label' => 'Artwork alignment', 'default' => 'center', 'choices' => ['left', 'center', 'right']],
                'header.show_title' => ['type' => 'choice', 'label' => 'Show site title', 'default' => 'yes', 'choices' => ['yes', 'no']],
                'header.tagline' => $text('Tagline', __('site.tagline')),
                'header.season' => $text('Season / event line', __('site.season')),
            ];
        }
        if ($group === 'home') {
            $fields = [];
            foreach (['hero_kicker', 'hero_title', 'hero_intro', 'enter', 'visit_forum', 'scene_caption', 'welcome_eyebrow', 'welcome_title', 'welcome_body'] as $key) {
                $fields['home.'.$key] = $text(ucwords(str_replace('_', ' ', $key)), __('site.home.'.$key));
            }

            return $fields + [
                'home.image' => $image('Hero artwork'), 'home.alt' => $text('Hero alternative text'),
                'home.welcome.image' => $image('Welcome box artwork'), 'home.welcome.alt' => $text('Welcome artwork alternative text'),
                'home.welcome.visible' => ['type' => 'choice', 'label' => 'Show welcome box', 'default' => 'yes', 'choices' => ['yes', 'no']],
                'home.before' => $blocks('Before the hero'), 'home.after' => $blocks('After the hero'),
                'home.features' => $blocks('Feature link boxes'), 'home.overview' => $blocks('Overview boxes'),
            ];
        }
        if ($group === 'sidebars') {
            $fields = [];
            foreach (['begin_here', 'find_way', 'guide_intro', 'read_guide', 'four_paths', 'field_notes', 'gates_open', 'shell_intro', 'view_rules', 'at_glance'] as $key) {
                $fields['shell.'.$key] = $text(ucwords(str_replace('_', ' ', $key)), __('site.shell.'.$key));
            }
            foreach (['welcome', 'paths', 'chat', 'about', 'stats'] as $key) {
                $fields['sidebar.'.$key.'.image'] = $image(ucfirst($key).' artwork');
                $fields['sidebar.'.$key.'.alt'] = $text(ucfirst($key).' alternative text');
                $fields['sidebar.'.$key.'.before'] = $blocks('Before '.ucfirst($key));
                $fields['sidebar.'.$key.'.after'] = $blocks('After '.ucfirst($key));
            }
            $fields['shell.outsiders'] = $text('Outsider section heading', __('site.nav.outsiders'));
            $fields['shell.chat_title'] = $text('Chatbox heading', __('chat.title'));
            if (Schema::hasTable('sidebar_modules')) {
                foreach (SidebarModule::orderBy('id')->get() as $module) {
                    $prefix = 'module.'.$module->id;
                    $fields[$prefix.'.title'] = $text('Module: '.$module->title, $module->title);
                    $fields[$prefix.'.body'] = ['type' => 'markdown', 'label' => $module->title.' body', 'default' => $module->body];
                    $fields[$prefix.'.visible'] = ['type' => 'choice', 'label' => $module->title.' visibility', 'default' => 'yes', 'choices' => ['yes', 'no']];
                }
            }

            return $fields;
        }
        if (str_starts_with($group, 'page:') && array_key_exists(substr($group, 5), self::pages())) {
            $key = substr($group, 5);
            $prefix = 'page.'.$key.'.';
            $fields = [
                $prefix.'eyebrow' => $text('Eyebrow'), $prefix.'title' => $text('Title (dashboard: greeting before member name)'),
                $prefix.'intro' => $text('Introduction'),
                $prefix.'image' => $image('Page banner artwork'), $prefix.'alt' => $text('Banner alternative text'),
                $prefix.'before' => $blocks('Above page heading'), $prefix.'after' => $blocks('Below page heading'),
            ];
            if (in_array($key, ['forum.board', 'forum.thread', 'characters.edit', 'characters.show', 'members.show', 'adoption.show'], true)) {
                unset($fields[$prefix.'eyebrow'], $fields[$prefix.'title'], $fields[$prefix.'intro']);
            }
            if ($key === 'content.map') {
                $fields['map.image'] = $image('Interactive map (781 x 781; same territory positions required)');
            }
            if ($key === 'sales.index') {
                $fields['sales.body'] = ['type' => 'markdown', 'label' => 'Sales introduction', 'default' => "## Find a new story\n\nBrowse the characters available for purchase and read each profile before deciding. The listed price is paid in crickets; ownership transfers to the buyer when the purchase succeeds."];
            }
            foreach (self::actions()[$key] ?? [] as $action => $label) {
                $fields[$prefix.'action.'.$action] = $text('Button: '.$label, $label);
            }

            return $fields;
        }

        return [];
    }

    public static function actions(): array
    {
        return [
            'characters' => ['memorial' => 'Visit the memorial', 'create' => 'Create a character'],
            'adoption.index' => ['create' => 'Create an adoptable'],
            'sales.index' => ['create' => 'List a character'],
        ];
    }

    public static function featureDefaults(): array
    {
        return collect(['character' => '/characters', 'allegiance' => '/clans', 'story' => '/forum'])
            ->map(fn ($url, $key) => ['id' => $key, 'eyebrow' => ['character' => '01', 'allegiance' => '02', 'story' => '03'][$key], 'title' => __('site.home.paths.'.$key.'.title'), 'body' => __('site.home.paths.'.$key.'.body'), 'link' => $url, 'label' => '', 'image' => null, 'alt' => ''])->values()->all();
    }

    public static function overviewDefaults(): array
    {
        return collect(['paths' => '/clans', 'guide' => '/guide'])->map(fn ($url, $key) => [
            'id' => $key, 'eyebrow' => __('site.home.overview.'.$key.'_eyebrow'), 'title' => __('site.home.overview.'.$key.'_title'),
            'body' => __('site.home.overview.'.$key.'_body'), 'link' => $url,
            'label' => __('site.home.overview.'.$key.'_link'), 'image' => null, 'alt' => '',
        ])->values()->all();
    }
}
