@if ($appearance->text('module.'.$module->id.'.visible', 'yes') !== 'no')
<section class="sidebar-card">
    <p class="eyebrow">{{ $appearance->text('module.'.$module->id.'.title', $module->title) }}</p>
    <div>{!! \Illuminate\Support\Str::markdown($appearance->text('module.'.$module->id.'.body', $module->body), ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</div>
    @if ($module->link_text && $module->link_url)<a class="text-link" href="{{ $module->link_url }}">{{ $module->link_text }} <span aria-hidden="true">↗</span></a>@endif
</section>
@endif
