<?php

namespace App\Providers;

use App\Models\Character;
use App\Models\ForumThread;
use App\Models\SidebarModule;
use App\Models\WorldPage;
use App\Support\CavernasRules;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.cavernas', function ($view): void {
            $modules = Schema::hasTable('sidebar_modules')
                ? SidebarModule::query()->where('is_enabled', true)->orderBy('placement')->orderBy('sort_order')->get()
                : collect();
            $worldPages = Schema::hasTable('world_pages')
                ? WorldPage::query()->whereIn('kind', ['clans', 'outsiders'])->orderBy('sort_order')->orderBy('id')->get()->groupBy('kind')
                : collect();
            $atGlance = ['openStories' => 0, 'livingCharacters' => 0, 'clanPopulation' => 0, 'outsiders' => 0, 'lowestClan' => '—', 'lowestCount' => 0];
            if (Schema::hasTable('forum_threads')) {
                $atGlance['openStories'] = ForumThread::where('is_locked', false)->count();
            }
            if (Schema::hasTable('characters')) {
                $clans = ['ThunderClan', 'RiverClan', 'ShadowClan', 'WindClan'];
                $counts = array_replace(array_fill_keys($clans, 0), CavernasRules::clanPopulationCounts());
                asort($counts);
                $atGlance['livingCharacters'] = Character::whereIn('status', ['active', 'inactive'])->count();
                $atGlance['clanPopulation'] = array_sum($counts);
                $atGlance['outsiders'] = Character::whereIn('allegiance', ['outsider', 'Kittypet', 'Loner', 'Rogue'])->whereIn('status', ['active', 'inactive'])->count();
                $atGlance['lowestClan'] = str_replace('Clan', '', (string) array_key_first($counts));
                $atGlance['lowestCount'] = (int) reset($counts);
            }
            $view->with(['sidebarModules' => $modules, 'worldPages' => $worldPages, 'atGlance' => $atGlance]);
        });
    }
}
