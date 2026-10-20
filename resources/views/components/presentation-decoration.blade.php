@php($key = 'page.'.\App\Support\SitePresentation::pageKey())
<x-presentation-blocks :name="$key.'.before'" />
<x-presentation-art :name="$key" />
<x-presentation-blocks :name="$key.'.after'" />
