<details class="theme-block-editor" data-theme-block @if (empty($block)) open @endif>
    <summary><strong data-block-title>{{ $block['title'] ?? 'New card' }}</strong><span class="theme-summary-action" data-block-summary>{{ ! empty($block['remove']) ? 'Will be removed' : 'Edit card' }}</span></summary>
    <div class="theme-block-fields">
        @if (! empty($block['id']) && \Illuminate\Support\Str::isUuid($block['id']))<input type="hidden" name="{{ $blockPrefix }}[id]" value="{{ $block['id'] }}">@endif
        <label>Card title<input name="{{ $blockPrefix }}[title]" value="{{ $block['title'] ?? '' }}" maxlength="255" data-block-title-input></label>
        <label>Text<textarea name="{{ $blockPrefix }}[body]" rows="4" maxlength="20000">{{ $block['body'] ?? '' }}</textarea></label>
        <div data-rich-editor data-lazy-editor data-editor-height="280px" data-editor-target="{{ $blockPrefix }}[body]" data-upload-url="{{ route('admin.themes.images') }}" aria-label="Card text editor"></div>
        <details class="theme-card-extras" @if (! empty($block['image'])) open @endif><summary>Add an image or link <small>Optional</small></summary>
            <label>Small label or number above the title<input name="{{ $blockPrefix }}[eyebrow]" value="{{ $block['eyebrow'] ?? '' }}" maxlength="120"></label>
            <div class="theme-block-upload" data-theme-upload data-upload-url="{{ route('admin.themes.images') }}">
                <label>Card image<input type="file" accept="image/jpeg,image/png,image/gif,image/webp" data-artwork-file></label>
                <input type="hidden" name="{{ $blockPrefix }}[image]" value="{{ $block['image'] ?? '' }}" data-artwork-path>
                <img class="theme-thumbnail" data-artwork-preview @if (! empty($block['image'])) src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($block['image']) }}" @else hidden @endif alt="Card artwork">
                <button class="theme-link-button" type="button" data-clear-artwork>Remove image</button><p data-upload-status role="status"></p>
            </div>
            <label>Image description<input name="{{ $blockPrefix }}[alt]" value="{{ $block['alt'] ?? '' }}" maxlength="255"></label>
            <label>Where should the link go?<input name="{{ $blockPrefix }}[link]" value="{{ $block['link'] ?? '' }}" maxlength="2000" placeholder="/guide or https://example.com"></label>
            <label>Link or button text<input name="{{ $blockPrefix }}[label]" value="{{ $block['label'] ?? '' }}" maxlength="120" placeholder="e.g. Read the guide"></label>
        </details>
        <div class="theme-block-actions"><button class="button theme-button-secondary" type="button" data-move-block="-1">Move up</button><button class="button theme-button-secondary" type="button" data-move-block="1">Move down</button><label class="checkbox-label"><input type="checkbox" name="{{ $blockPrefix }}[remove]" value="1" @checked($block['remove'] ?? false) data-block-remove> Remove this card when saved</label></div>
    </div>
</details>
