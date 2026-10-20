@php
    $prefix = 'fields['.$index.']';
    $fallback = $field['default'];
    if ($key === 'home.features') $fallback = \App\Support\SitePresentation::featureDefaults();
    if ($key === 'home.overview') $fallback = \App\Support\SitePresentation::overviewDefaults();
    $default = $base[$key] ?? $fallback;
    if (! $theme) $default = $fallback;
    $current = array_key_exists($key, $values) ? $values[$key] : $default;
    $saved = array_key_exists($key, $values);
    $mode = old("fields.$index.mode", $saved ? (($values[$key] === '' || $values[$key] === []) ? 'hidden' : 'custom') : 'inherit');
    $current = old("fields.$index.value", $current);
    $label = \App\Support\SitePresentation::fieldLabel($key, $field['label']);
    $defaultLabel = $theme ? 'Use site default' : 'Use original content';
    $isBlocks = $field['type'] === 'blocks';
    $isImage = $field['type'] === 'image';
@endphp
<div class="theme-field" data-theme-field data-field-type="{{ $field['type'] }}" data-default-value="{{ is_array($default) ? '' : $default }}" @if ($isImage && $default) data-default-image-url="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($default) }}" @endif data-default-source="{{ $theme ? 'Using site default' : 'Using original content' }}">
    <input type="hidden" name="{{ $prefix }}[key]" value="{{ $key }}">
    <div class="theme-field-heading"><label for="theme-value-{{ $index }}">{{ $label }}</label><span class="theme-field-source" data-field-source>{{ $mode === 'inherit' ? ($theme ? 'Using site default' : 'Using original content') : ($mode === 'hidden' ? 'Hidden' : 'Custom') }}</span></div>
    @if ($key === 'page.dashboard.title')<p class="theme-field-help">The greeting before the member's name. Their name and profile link are added automatically.</p>@endif
    <div class="theme-field-settings">
        <select name="{{ $prefix }}[mode]" aria-label="{{ $label }} option" data-theme-mode><option value="inherit" @selected($mode === 'inherit')>{{ $defaultLabel }}</option><option value="custom" @selected($mode === 'custom')>Custom</option><option value="hidden" @selected($mode === 'hidden')>Hide this item</option></select>
    </div>
    <div data-field-content>
        @if ($isBlocks)
            <span id="theme-value-{{ $index }}" class="theme-field-help">Each card can contain text, an image, and a link. Open a card to edit it.</span>
            <div data-theme-block-list data-prefix="{{ $prefix }}[blocks]">
                @foreach (old("fields.$index.blocks", is_array($current) ? $current : []) as $block)
                    @include('admin.themes.block', ['block' => $block, 'blockPrefix' => $prefix.'[blocks]['.$loop->index.']'])
                @endforeach
            </div>
            <p class="theme-empty-small" data-block-empty @if (is_array($current) && count($current)) hidden @endif>No extra cards here yet. Add one only if you need it.</p>
            <button class="button theme-button-secondary" type="button" data-add-theme-block>+ Add a card</button>
            <template data-theme-block-template>@include('admin.themes.block', ['block' => [], 'blockPrefix' => '__PREFIX__'])</template>
            <template data-default-blocks>@foreach (is_array($default) ? $default : [] as $block)@include('admin.themes.block', ['block' => $block, 'blockPrefix' => $prefix.'[blocks]['.$loop->index.']'])@endforeach</template>
        @elseif ($isImage)
            <div class="theme-image-picker">
                <img class="theme-thumbnail" data-field-image-preview @if ($current) src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($current) }}" @else hidden @endif alt="Current {{ $label }}">
                <span class="theme-image-empty" data-field-image-empty @if ($current) hidden @endif>No replacement image. This area keeps its original appearance until you choose an image or hide it.</span>
                <input type="hidden" name="{{ $prefix }}[value]" value="{{ $current }}">
                <label class="theme-upload-label">Choose or replace image<input id="theme-value-{{ $index }}" type="file" name="{{ $prefix }}[image]" accept="image/jpeg,image/png,image/gif,image/webp" data-field-image></label>
                <small data-field-file-name>JPG, PNG, GIF, or WebP · Up to 5 MB</small>
            </div>
            @if ($key === 'map.image')<label class="checkbox-label theme-note"><input type="checkbox" name="map_confirm" value="1"> This map is 781 x 781 and keeps the same territory positions, so the existing clickable links still match.</label>@endif
        @elseif ($field['type'] === 'choice')
            <select id="theme-value-{{ $index }}" name="{{ $prefix }}[value]" aria-label="{{ $label }}">@foreach ($field['choices'] as $option)<option value="{{ $option }}" @selected($current === $option)>{{ ['contain' => 'Show the whole image', 'cover' => 'Fill the space (edges may be cropped)', 'yes' => 'Show', 'no' => 'Hide'][$option] ?? ucfirst($option) }}</option>@endforeach</select>
        @else
            <textarea id="theme-value-{{ $index }}" name="{{ $prefix }}[value]" rows="{{ $field['type'] === 'markdown' ? 6 : 2 }}" maxlength="20000" @if ($current === null) placeholder="Keep the existing page text, or type a replacement here." @endif>{{ $current }}</textarea>
            @if ($field['type'] === 'markdown')<div data-rich-editor data-lazy-editor data-editor-height="280px" data-editor-target="{{ $prefix }}[value]" data-upload-url="{{ route('admin.themes.images') }}" aria-label="{{ $label }}"></div>@endif
            @if (str_ends_with($key, '.alt'))<small class="theme-field-help">Describe the image for visitors who cannot see it. Leave empty if it is purely decorative.</small>@endif
        @endif
    </div>
    <p class="theme-note" data-field-hidden-message @if ($mode !== 'hidden') hidden @endif>This item is hidden. Save to apply, or choose Show & edit to bring it back.</p>
    <div class="theme-field-tools" hidden data-field-tools>
        <button type="button" class="theme-link-button" data-field-reset>{{ $defaultLabel }}</button>
        @unless ($field['type'] === 'choice')<button type="button" class="theme-link-button" data-field-hide>{{ $isImage ? 'Remove image' : ($isBlocks ? 'Hide these cards' : 'Hide this text') }}</button>@endunless
        <button type="button" class="theme-link-button" data-field-restore @if ($mode !== 'hidden') hidden @endif>Show & edit</button>
    </div>
    @error('fields.'.$index.'.value')<p class="theme-field-error" role="alert">{{ $message }}</p>@enderror
    @error('fields.'.$index.'.image')<p class="theme-field-error" role="alert">{{ $message }}</p>@enderror
</div>
