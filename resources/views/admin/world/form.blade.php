@extends('layouts.staff')

@section('content')
<section class="page-heading page-heading-theme compact-heading"><p class="eyebrow">Administrator panel · {{ ucfirst($page->kind) }}</p><h1>{{ $page->exists ? 'Edit' : 'Add' }}<br><em>{{ $page->exists ? $page->name : ($page->kind === 'clans' ? 'a clan.' : 'an outsider.') }}</em></h1></section>
@if (session('status'))<div class="form-success" role="status">{{ session('status') }}</div>@endif
@if ($errors->any())<div class="form-alert" role="alert">{{ $errors->first() }}</div>@endif
<section class="admin-management-card guide-admin-panel">
    <form method="POST" action="{{ $page->exists ? route('admin.world.update', $page) : route('admin.world.store') }}" class="cavernas-form guide-admin-form" data-article-form>
        @csrf @if ($page->exists) @method('PATCH') @else <input type="hidden" name="kind" value="{{ $page->kind }}"> @endif
        <div class="form-columns">
            <label>Name<input name="name" value="{{ old('name', $page->name) }}" maxlength="120" required></label>
            @if ($page->exists)
                <label>Page URL<input value="{{ route('world.show', ['kind' => $page->kind, 'slug' => $page->slug]) }}" readonly></label>
            @else
                <label>URL slug<input name="slug" value="{{ old('slug') }}" pattern="[a-z0-9]+(-[a-z0-9]+)*" placeholder="e.g. thunderclan" maxlength="120" required></label>
            @endif
        </div>
        <label>Index description<textarea name="summary" rows="2" maxlength="1000" required>{{ old('summary', $page->summary) }}</textarea></label>
        <div class="form-columns">
            <label>Eyebrow<input name="eyebrow" value="{{ old('eyebrow', $page->eyebrow) }}" maxlength="80" required></label>
            <label>Page title<textarea name="title" rows="2" maxlength="255" required>{{ old('title', $page->title) }}</textarea></label>
        </div>
        <label>Introduction<textarea name="intro" rows="2" maxlength="1000" required>{{ old('intro', $page->intro) }}</textarea></label>
        <label>Order<input name="sort_order" type="number" min="0" value="{{ old('sort_order', $page->sort_order ?? 0) }}" required></label>
        <label for="world-body">Article content</label>
        <textarea id="world-body" name="body" rows="20" required>{{ old('body', $page->body) }}</textarea>
        <div data-article-editor data-upload-url="{{ route('admin.world.images.store') }}" aria-label="World page editor"></div>
        <div class="guide-admin-actions"><a class="text-link" href="{{ route('admin.world.index') }}">All world pages</a>@if ($page->exists)<a class="text-link" href="{{ route('world.show', ['kind' => $page->kind, 'slug' => $page->slug]) }}">View page</a>@endif<button class="button button-primary" type="submit">{{ $page->exists ? 'Save page' : 'Create page' }}</button></div>
    </form>
    @if ($page->exists)
        <form method="POST" action="{{ route('admin.world.destroy', $page) }}" class="world-admin-delete" onsubmit="return confirm('Delete this page?')">@csrf @method('DELETE')<button class="button rule-delete" type="submit">Delete page</button></form>
    @endif
</section>
@endsection