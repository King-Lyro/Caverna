<div class="theme-area-intro"><h2>Name & colors</h2><p>Choose a name you recognize. The sample shows how the colors work together; save and preview to see your actual site.</p></div>
<form method="POST" action="{{ route('admin.themes.update', $theme) }}" class="cavernas-form theme-editor" data-track-theme-changes data-theme-colors>
    @csrf @method('PATCH')
    <section class="theme-panel"><label>Theme name<input name="name" value="{{ old('name', $theme->name) }}" maxlength="120" required></label></section>
    <div class="theme-color-sample" data-color-sample aria-label="Color sample, not a live site preview">
        <span class="theme-badge">Color sample</span><div class="theme-color-sample-heading"><small>Welcome to Cavernas</small><h3>Your stories start here.</h3></div>
        <div class="theme-color-sample-card"><strong>A place for your next adventure</strong><p>This is how your text and content boxes will look.</p><span>Sample button</span></div>
    </div>
    <section class="theme-panel"><h3>Everyday colors</h3><p>These four choices make the biggest difference.</p><div class="theme-color-grid">
        @foreach (['accent', 'bg', 'white', 'ink'] as $key)
            @include('admin.themes.color')
        @endforeach
    </div></section>
    <details class="theme-editor-section"><summary><span><strong>Fine-tune the details</strong><small>Optional: borders, captions, and supporting colors.</small></span><span class="theme-summary-action">6 colors</span></summary><div class="theme-color-grid theme-section-fields">
        @foreach (['deep', 'muted', 'soft', 'warm', 'line', 'paper'] as $key)
            @include('admin.themes.color')
        @endforeach
    </div></details>
    @include('admin.themes.save-bar')
</form>
