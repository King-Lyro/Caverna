@extends('layouts.staff')

@section('content')
<section class="page-heading page-heading-theme compact-heading"><p class="eyebrow">Administrator panel</p><h1>World<br><em>pages.</em></h1><p>Write the stories behind each clan and outsider path.</p></section>
@if (session('status'))<div class="form-success" role="status">{{ session('status') }}</div>@endif
<div class="world-admin-groups">
    @foreach (['clans' => 'Clans', 'outsiders' => 'Outsiders'] as $kind => $label)
        <section class="admin-management-card world-admin-group">
            <div class="world-admin-heading"><h2>{{ $label }}</h2><a class="button button-primary" href="{{ route('admin.world.create', ['kind' => $kind]) }}">Add {{ $kind === 'clans' ? 'clan' : 'outsider' }}</a></div>
            @forelse ($pages->get($kind, collect()) as $page)
                <div class="world-admin-row"><div><strong>{{ $page->name }}</strong><p>{{ $page->summary }}</p></div><a class="text-link" href="{{ route('admin.world.edit', $page) }}">Edit</a></div>
            @empty
                <p>No pages yet.</p>
            @endforelse
        </section>
    @endforeach
</div>
@endsection