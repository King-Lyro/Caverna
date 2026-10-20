@props(['name'])
@php($presentation = \App\Services\SiteAppearance::current())
@if ($presentation->image($name.'.image'))
<img {{ $attributes->class(['presentation-art']) }} src="{{ $presentation->image($name.'.image') }}" alt="{{ $presentation->text($name.'.alt') }}">
@endif
