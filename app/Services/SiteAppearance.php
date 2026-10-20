<?php

namespace App\Services;

use App\Models\SiteTheme;
use App\Models\SiteThemeSchedule;
use App\Support\SitePresentation;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class SiteAppearance
{
    public string $key = 'fall';

    public string $manual = 'fall';

    public bool $scheduling = true;

    public bool $preview = false;

    public bool $stale = false;

    public ?SiteThemeSchedule $schedule = null;

    public Collection $themes;

    public array $colors = [];

    private array $values = [];

    public static function current(): self
    {
        $request = request();
        if (! $request->attributes->has('siteAppearance')) {
            $appearance = new self;
            $appearance->load();
            $request->attributes->set('siteAppearance', $appearance);
        }

        return $request->attributes->get('siteAppearance');
    }

    private function load(): void
    {
        $this->themes = collect();
        if (! Schema::hasTable('site_settings')) {
            return;
        }
        $settings = DB::table('site_settings')->whereIn('key', ['theme', 'theme_scheduling', 'site_presentation'])->pluck('value', 'key');
        $this->manual = $settings['theme'] ?? 'fall';
        $this->scheduling = ($settings['theme_scheduling'] ?? '1') === '1';
        if (! Schema::hasTable('site_themes')) {
            $this->key = array_key_exists($this->manual, SitePresentation::palettes()) ? $this->manual : 'fall';

            return;
        }
        $this->themes = SiteTheme::orderBy('is_builtin', 'desc')->orderBy('name')->get();
        $this->stale = ! $this->themes->contains('key', $this->manual);
        $this->key = $this->stale ? 'fall' : $this->manual;
        if ($this->scheduling) {
            $calendar = new ThemeCalendar;
            $this->schedule = SiteThemeSchedule::with('theme')->where('is_enabled', true)->orderBy('id')->get()
                ->first(fn ($schedule) => $calendar->matches($schedule, CarbonImmutable::now(config('app.timezone'))));
            if ($this->schedule) {
                $this->key = $this->schedule->theme->key;
            }
        }
        if (request()->hasSession() && request()->user()?->isAdmin()) {
            $previewKey = request()->session()->get('theme_preview');
            if (is_string($previewKey) && $this->themes->contains('key', $previewKey)) {
                $this->key = $previewKey;
                $this->preview = true;
            }
        }
        $theme = $this->themes->firstWhere('key', $this->key);
        $base = json_decode($settings['site_presentation'] ?? '{}', true, 512, JSON_THROW_ON_ERROR);
        $this->values = array_replace($base, $theme?->presentation ?? []);
        $this->colors = $theme?->colors ?? [];
    }

    public function get(string $key, mixed $fallback = null): mixed
    {
        return array_key_exists($key, $this->values) ? $this->values[$key] : $fallback;
    }

    public function text(string $key, ?string $fallback = ''): string
    {
        return (string) $this->get($key, $fallback);
    }

    public function image(string $key): ?string
    {
        $path = $this->get($key);

        return $path ? Storage::disk('public')->url($path) : null;
    }

    public function blocks(string $key, array $fallback = []): array
    {
        return $this->get($key, $fallback) ?? [];
    }

    public function style(): string
    {
        $result = [];
        foreach (array_keys(SitePresentation::palettes()['fall']) as $name) {
            $value = $this->colors[$name] ?? null;
            if (is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/D', $value)) {
                $result[] = '--'.$name.':'.$value;
            }
        }

        return implode(';', $result);
    }
}
