<?php

namespace App\Providers;

use App\Models\SidebarModule;
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
            $view->with('sidebarModules', $modules);
        });
    }
}
