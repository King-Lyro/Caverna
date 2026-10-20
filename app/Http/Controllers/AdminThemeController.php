<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\SiteTheme;
use App\Models\SiteThemeSchedule;
use App\Services\ThemeCalendar;
use App\Support\SitePresentation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class AdminThemeController extends Controller
{
    public function index(): View
    {
        $tab = request()->query('tab', 'themes');
        abort_unless(in_array($tab, ['themes', 'schedules', 'defaults'], true), 404);

        return view('admin.themes.index', [
            'tab' => $tab,
            'themes' => SiteTheme::with('schedules')->orderBy('is_builtin', 'desc')->orderBy('name')->get(),
            'schedules' => SiteThemeSchedule::with('theme')->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'source_theme_id' => ['nullable', 'integer', 'exists:site_themes,id'],
        ]);
        $theme = DB::transaction(function () use ($data, $request) {
            $source = isset($data['source_theme_id']) ? SiteTheme::findOrFail($data['source_theme_id']) : null;
            $theme = SiteTheme::create([
                'key' => Str::limit(Str::slug($data['name']) ?: 'theme', 90, '').'-'.Str::lower(Str::random(10)),
                'name' => $data['name'], 'colors' => $source?->colors ?? SitePresentation::palettes()['fall'],
                'presentation' => $source?->presentation ?? [],
            ]);
            $this->audit($request, 'theme.created', $theme);

            return $theme;
        });

        return redirect()->route('admin.themes.edit', $theme)->with('status', 'Your theme is ready to edit. Visitors will not see it until you choose to use it.');
    }

    public function edit(Request $request, ?SiteTheme $theme = null): View
    {
        $group = $request->query('group', 'header');
        abort_unless(is_string($group) && (array_key_exists($group, SitePresentation::groups()) || ($theme && $group === 'colors')), 404);
        $base = json_decode(DB::table('site_settings')->where('key', 'site_presentation')->value('value') ?? '{}', true, 512, JSON_THROW_ON_ERROR);

        return view('admin.themes.edit', [
            'theme' => $theme, 'group' => $group, 'groups' => SitePresentation::groups(),
            'fields' => SitePresentation::fields($group), 'values' => $theme?->presentation ?? $base,
            'base' => $base,
            'sections' => SitePresentation::editorSections($group),
        ]);
    }

    public function update(Request $request, SiteTheme $theme): RedirectResponse
    {
        $rules = ['name' => ['required', 'string', 'max:120'], 'colors' => ['required', 'array:'.implode(',', array_keys(SitePresentation::palettes()['fall']))]];
        foreach (array_keys(SitePresentation::palettes()['fall']) as $key) {
            $rules['colors.'.$key] = ['required', 'regex:/^#[0-9a-fA-F]{6}$/D'];
        }
        $data = $request->validate($rules + ['preview' => ['sometimes', 'boolean']]);
        $preview = (bool) ($data['preview'] ?? false);
        unset($data['preview']);
        DB::transaction(function () use ($theme, $data, $request): void {
            $theme->update($data);
            $this->audit($request, 'theme.updated', $theme);
        });

        return $this->saved($request, $theme, $preview, 'Theme name and colors saved.');
    }

    public function presentation(Request $request, ?SiteTheme $theme = null): RedirectResponse
    {
        $group = $request->input('group');
        abort_unless(is_string($group) && array_key_exists($group, SitePresentation::groups()), 404);
        $definitions = SitePresentation::fields($group);
        $data = $request->validate([
            'form_complete' => ['required', 'in:1'],
            'preview' => ['sometimes', 'boolean'],
            'fields' => ['required', 'array', 'size:'.count($definitions)],
            'fields.*' => ['required', 'array:key,mode,value,image,blocks'],
            'fields.*.key' => ['required', Rule::in(array_keys($definitions))],
            'fields.*.mode' => ['required', Rule::in(['inherit', 'custom', 'hidden'])],
            'fields.*.value' => ['nullable', 'string', 'max:20000'],
            'fields.*.image' => ['nullable', ...$this->imageRules()],
            'fields.*.blocks' => ['nullable', 'array', 'max:30'],
            'fields.*.blocks.*' => ['array:id,eyebrow,title,body,image,alt,link,label,remove'],
            'fields.*.blocks.*.id' => ['nullable', 'uuid'],
            'fields.*.blocks.*.eyebrow' => ['nullable', 'string', 'max:120'],
            'fields.*.blocks.*.title' => ['nullable', 'string', 'max:255'],
            'fields.*.blocks.*.body' => ['nullable', 'string', 'max:20000'],
            'fields.*.blocks.*.image' => ['nullable', 'string', 'max:255'],
            'fields.*.blocks.*.alt' => ['nullable', 'string', 'max:255'],
            'fields.*.blocks.*.link' => ['nullable', 'string', 'max:2000'],
            'fields.*.blocks.*.label' => ['nullable', 'string', 'max:120'],
            'fields.*.blocks.*.remove' => ['nullable', 'boolean'],
            'map_confirm' => ['sometimes', 'accepted'],
        ], ['form_complete.required' => 'The presentation form was truncated. Reduce the number of boxes or ask the server administrator to increase PHP max_input_vars. No changes were saved.']);
        $uploads = [];
        try {
            DB::transaction(function () use ($data, $definitions, $theme, $request, &$uploads): void {
                $this->lockSettings();
                $values = $theme
                    ? SiteTheme::findOrFail($theme->id)->presentation
                    : json_decode(DB::table('site_settings')->where('key', 'site_presentation')->value('value') ?? '{}', true, 512, JSON_THROW_ON_ERROR);
                foreach (array_values(array_keys($definitions)) as $index => $key) {
                    $definition = $definitions[$key];
                    $field = $data['fields'][$index] ?? throw ValidationException::withMessages(['fields' => 'Invalid field order. Reload the editor.']);
                    if ($field['key'] !== $key) {
                        throw ValidationException::withMessages(['fields' => 'These presentation fields changed while the editor was open. Reload the editor before saving.']);
                    }
                    if ($field['mode'] === 'inherit') {
                        unset($values[$key]);

                        continue;
                    }
                    if ($field['mode'] === 'hidden') {
                        $values[$key] = match ($definition['type']) {
                            'blocks' => [],
                            'choice' => in_array('no', $definition['choices'], true) ? 'no' : $definition['default'],
                            default => '',
                        };

                        continue;
                    }
                    $value = $field['value'] ?? '';
                    if ($definition['type'] === 'choice' && ! in_array($value, $definition['choices'], true)) {
                        throw ValidationException::withMessages(["fields.$index.value" => 'Choose a valid display option.']);
                    }
                    if ($definition['type'] === 'image') {
                        if (isset($field['image'])) {
                            if ($key === 'map.image') {
                                $request->validate(["fields.$index.image" => ['dimensions:width=781,height=781'], 'map_confirm' => ['required', 'accepted']]);
                            }
                            $value = Storage::disk('public')->putFile('themes', $field['image']);
                            abort_unless(is_string($value), 500, 'Artwork could not be saved.');
                            $uploads[] = $value;
                        } else {
                            $this->validateAsset($value);
                            if ($key === 'map.image' && $value !== ($values[$key] ?? '')) {
                                throw ValidationException::withMessages(["fields.$index.image" => 'Upload map artwork and confirm that territory positions are unchanged.']);
                            }
                        }
                    }
                    if ($definition['type'] === 'blocks') {
                        $value = [];
                        foreach ($field['blocks'] ?? [] as $block) {
                            if (! empty($block['remove'])) {
                                continue;
                            }
                            $this->validateAsset($block['image'] ?? '');
                            $this->validateLink($block['link'] ?? '');
                            $block['id'] = $block['id'] ?? (string) Str::uuid();
                            unset($block['remove']);
                            $value[] = $block;
                        }
                    }
                    $values[$key] = $value;
                }
                if ($theme) {
                    $theme->update(['presentation' => $values]);
                } else {
                    $this->setting('site_presentation', json_encode($values, JSON_THROW_ON_ERROR));
                }
                $this->audit($request, 'theme.presentation_updated', $theme, ['group' => $request->input('group'), 'shared_base' => ! $theme]);
            });
        } catch (Throwable $exception) {
            foreach ($uploads as $path) {
                if (! Storage::disk('public')->delete($path)) {
                    report(new \RuntimeException('Failed to clean up theme upload: '.$path));
                }
            }
            throw $exception;
        }

        return $this->saved($request, $theme, (bool) ($data['preview'] ?? false), 'Changes saved.');
    }

    public function duplicate(Request $request, SiteTheme $theme): RedirectResponse
    {
        $copy = DB::transaction(function () use ($theme, $request) {
            $copy = SiteTheme::create([
                'key' => 'theme-'.Str::lower(Str::random(16)), 'name' => Str::limit($theme->name, 100, '').' (copy)',
                'colors' => $theme->colors, 'presentation' => $theme->presentation,
            ]);
            $this->audit($request, 'theme.duplicated', $copy, ['source' => $theme->id]);

            return $copy;
        });

        return redirect()->route('admin.themes.edit', $copy)->with('status', 'Theme duplicated without activating it or copying its schedules.');
    }

    public function destroy(Request $request, SiteTheme $theme): RedirectResponse
    {
        DB::transaction(function () use ($request, $theme): void {
            $this->lockSettings();
            $theme = SiteTheme::lockForUpdate()->findOrFail($theme->id);
            if ($theme->is_builtin || DB::table('site_settings')->where('key', 'theme')->value('value') === $theme->key || $theme->schedules()->where('is_enabled', true)->exists()) {
                throw ValidationException::withMessages(['theme' => 'Built-in, manually selected, or enabled scheduled themes cannot be deleted. Switch themes and disable its schedules first.']);
            }
            $this->audit($request, 'theme.deleted', $theme);
            $theme->schedules()->delete();
            $theme->delete();
        });
        if ($request->session()->get('theme_preview') === $theme->key) {
            $request->session()->forget('theme_preview');
        }

        return back()->with('status', 'Theme deleted. Shared artwork has been retained.');
    }

    public function preview(Request $request, SiteTheme $theme): RedirectResponse
    {
        $request->session()->put('theme_preview', $theme->key);

        return redirect()->route('home');
    }

    public function stopPreview(Request $request): RedirectResponse
    {
        $request->session()->forget('theme_preview');

        return redirect()->route('admin.themes.index')->with('status', 'Preview ended.');
    }

    public function upload(Request $request): JsonResponse
    {
        $data = $request->validate(['image' => ['required', ...$this->imageRules()]]);
        $path = Storage::disk('public')->putFile('themes', $data['image']);
        abort_unless(is_string($path), 500, 'Artwork could not be saved.');

        return response()->json(['path' => $path, 'url' => Storage::disk('public')->url($path)]);
    }

    public function scheduling(Request $request): RedirectResponse
    {
        $data = $request->validate(['enabled' => ['required', 'boolean']]);
        DB::transaction(function () use ($data, $request): void {
            $this->lockSettings();
            $this->setting('theme_scheduling', $data['enabled'] ? '1' : '0');
            $this->audit($request, 'theme.scheduling_changed', null, $data);
        });

        return back()->with('status', $data['enabled'] ? 'Scheduling resumed.' : 'Scheduling paused. The manually selected theme is now used.');
    }

    public function saveSchedule(Request $request, ?SiteThemeSchedule $schedule = null): RedirectResponse
    {
        $data = $request->validate([
            'site_theme_id' => ['required', 'integer', 'exists:site_themes,id'],
            'name' => ['required', 'string', 'max:120'],
            'recurrence' => ['required', Rule::in(['once', 'annual'])],
            'starts_on' => ['exclude_unless:recurrence,once', 'required', 'date_format:Y-m-d', 'after_or_equal:1900-01-01', 'before_or_equal:9998-12-31'],
            'ends_on' => ['exclude_unless:recurrence,once', 'required', 'date_format:Y-m-d', 'after_or_equal:starts_on', 'before_or_equal:9998-12-31'],
            'starts_md' => ['exclude_unless:recurrence,annual', 'required', 'regex:/^\d{2}-\d{2}$/D'],
            'ends_md' => ['exclude_unless:recurrence,annual', 'required', 'regex:/^\d{2}-\d{2}$/D'],
            'is_enabled' => ['required', 'boolean'],
        ]);
        foreach (['starts_md', 'ends_md'] as $key) {
            if (isset($data[$key])) {
                [$month, $day] = array_map('intval', explode('-', $data[$key]));
                if (! checkdate($month, $day, 2000)) {
                    throw ValidationException::withMessages([$key => 'Enter a valid month-day, such as 12-25 or 02-29.']);
                }
            }
        }
        $data += ['starts_on' => null, 'ends_on' => null, 'starts_md' => null, 'ends_md' => null];
        DB::transaction(function () use ($data, $request, $schedule): void {
            $this->lockSettings();
            $candidate = new SiteThemeSchedule($data);
            if ($candidate->is_enabled) {
                foreach (SiteThemeSchedule::where('is_enabled', true)->when($schedule, fn ($query) => $query->where('id', '!=', $schedule->id))->get() as $other) {
                    if ((new ThemeCalendar)->overlaps($candidate, $other)) {
                        throw ValidationException::withMessages(['starts_on' => 'This date range overlaps the enabled schedule "'.$other->name.'". Disable it or choose different dates.']);
                    }
                }
            }
            $saved = $schedule ?? new SiteThemeSchedule;
            $saved->fill($data)->save();
            $this->audit($request, 'theme.schedule_saved', $saved->theme, ['schedule' => $saved->only(array_keys($data))]);
        });

        return back()->with('status', 'Schedule saved.');
    }

    public function destroySchedule(Request $request, SiteThemeSchedule $schedule): RedirectResponse
    {
        DB::transaction(function () use ($request, $schedule): void {
            $this->lockSettings();
            $this->audit($request, 'theme.schedule_deleted', $schedule->theme, ['schedule_id' => $schedule->id]);
            $schedule->delete();
        });

        return back()->with('status', 'Schedule deleted.');
    }

    public function select(Request $request, string $key): RedirectResponse
    {
        $theme = SiteTheme::where('key', $key)->firstOrFail();
        DB::transaction(function () use ($theme, $request): void {
            $this->lockSettings();
            SiteTheme::findOrFail($theme->id);
            $this->setting('theme', $theme->key);
            $this->audit($request, 'theme.selected', $theme);
        });
        Cache::forget('site_theme');
        $request->session()->forget('theme_preview');

        return back()->with('status', 'Your active theme has been updated. Scheduled events still run on their dates unless automatic switching is paused.');
    }

    private function imageRules(): array
    {
        return ['image', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120', 'dimensions:max_width=6000,max_height=6000'];
    }

    private function saved(Request $request, ?SiteTheme $theme, bool $preview, string $message): RedirectResponse
    {
        if ($preview && $theme) {
            $request->session()->put('theme_preview', $theme->key);

            return redirect()->route('home')->with('status', $message);
        }

        return back()->with('status', $message);
    }

    private function validateAsset(?string $path): void
    {
        if ($path && (! preg_match('/^themes\/[a-zA-Z0-9_-]+\.(?:jpg|jpeg|png|gif|webp)$/D', $path) || ! Storage::disk('public')->exists($path))) {
            throw ValidationException::withMessages(['image' => 'Choose an existing uploaded theme image, or upload a new one.']);
        }
    }

    private function validateLink(?string $url): void
    {
        if ($url && ! ((str_starts_with($url, '/') && ! str_starts_with($url, '//') && ! preg_match('/[\\\\\\x00-\\x20]/', $url)) || (filter_var($url, FILTER_VALIDATE_URL) && in_array(strtolower(parse_url($url, PHP_URL_SCHEME) ?? ''), ['http', 'https'], true)))) {
            throw ValidationException::withMessages(['link' => 'Links must use an application path or an HTTP(S) URL.']);
        }
    }

    private function lockSettings(): void
    {
        DB::table('site_settings')->where('key', 'theme_scheduling')->update(['updated_at' => now()]);
        DB::table('site_settings')->where('key', 'theme_scheduling')->lockForUpdate()->first();
    }

    private function setting(string $key, string $value): void
    {
        DB::table('site_settings')->updateOrInsert(['key' => $key], ['value' => $value, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function audit(Request $request, string $action, ?SiteTheme $theme = null, array $changes = []): void
    {
        AuditLog::create([
            'user_id' => $request->user()->id, 'action' => $action,
            'auditable_type' => SiteTheme::class, 'auditable_id' => $theme?->id,
            'changes' => $changes, 'ip_address' => $request->ip(),
        ]);
    }
}
