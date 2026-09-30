@extends('layouts.staff')

@section('content')
<section class="page-heading page-heading-theme compact-heading"><p class="eyebrow">Administrator panel</p><h1>Edit the<br><em>guide.</em></h1><p>Keep the information new members need in one place.</p></section>
@if (session('status'))<div class="form-success" role="status">{{ session('status') }}</div>@endif
@if ($errors->any())<div class="form-alert" role="alert">{{ $errors->first() }}</div>@endif
<section class="admin-management-card guide-admin-panel">
    <form id="guide-form" method="POST" action="{{ route('admin.guide.update') }}" class="cavernas-form guide-admin-form" data-article-form>
        @csrf @method('PATCH')
        <div class="form-columns">
            <label for="guide-eyebrow">Eyebrow<input id="guide-eyebrow" name="eyebrow" value="{{ old('eyebrow', $page->eyebrow) }}" maxlength="80" required></label>
            <label for="guide-title">Page title<textarea id="guide-title" name="title" rows="2" maxlength="255" required>{{ old('title', $page->title) }}</textarea></label>
        </div>
        <label for="guide-intro">Introduction<textarea id="guide-intro" name="intro" rows="2" maxlength="1000" required>{{ old('intro', $page->intro) }}</textarea></label>
        <label for="guide-body">Article content</label>
        <textarea id="guide-body" name="body" rows="20" required>{{ old('body', $page->body) }}</textarea>
        <div id="guide-editor" data-article-editor data-upload-url="{{ route('admin.guide.images.store') }}" aria-label="Guide article editor"></div>
        <div class="guide-admin-actions"><a class="text-link" href="{{ route('content.page', 'guide') }}">View guide</a><button class="button button-primary" type="submit">Save guide</button></div>
    </form>
</section>
@endsection