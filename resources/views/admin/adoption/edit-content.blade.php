@extends('layouts.staff')

@section('content')
<section class="page-heading page-heading-theme compact-heading"><p class="eyebrow">Administrator panel</p><h1>Edit the<br><em>adoption page.</em></h1><p>Update the introduction members read before browsing available characters.</p></section>
@if (session('status'))<div class="form-success" role="status">{{ session('status') }}</div>@endif
@if ($errors->any())<div class="form-alert" role="alert">{{ $errors->first() }}</div>@endif
<section class="admin-management-card guide-admin-panel">
    <form method="POST" action="{{ route('admin.adoption.content.update') }}" class="cavernas-form guide-admin-form" data-article-form>
        @csrf @method('PATCH')
        <div class="form-columns">
            <label for="adoption-eyebrow">Eyebrow<input id="adoption-eyebrow" name="eyebrow" value="{{ old('eyebrow', $page->eyebrow) }}" maxlength="80" required></label>
            <label for="adoption-title">Page title<textarea id="adoption-title" name="title" rows="2" maxlength="255" required>{{ old('title', $page->title) }}</textarea></label>
        </div>
        <label for="adoption-intro">Introduction<textarea id="adoption-intro" name="intro" rows="2" maxlength="1000" required>{{ old('intro', $page->intro) }}</textarea></label>
        <label for="adoption-body">Content above listings</label>
        <textarea id="adoption-body" name="body" rows="20" required>{{ old('body', $page->body) }}</textarea>
        <div data-article-editor data-upload-url="{{ route('admin.adoption.content.images.store') }}" aria-label="Adoption page editor"></div>
        <div class="guide-admin-actions"><a class="text-link" href="{{ route('adoption.index') }}">View adoption page</a><button class="button button-primary" type="submit">Save page</button></div>
    </form>
</section>
@endsection