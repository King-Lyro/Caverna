@php([$label, $description] = \App\Support\SitePresentation::colorLabels()[$key])
<label class="theme-color-control"><input type="color" name="colors[{{ $key }}]" value="{{ old('colors.'.$key, $theme->colors[$key]) }}" data-color-key="{{ $key }}" required><span><strong>{{ $label }}</strong><small>{{ $description }}</small></span></label>
