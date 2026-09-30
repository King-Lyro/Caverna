@extends('layouts.staff')

@section('content')
<section class="page-heading page-heading-theme compact-heading"><p class="eyebrow">Administrator panel</p><h1>Edit the<br><em>{{ $slug }} page.</em></h1><p>Keep this page's legal and contact information accurate.</p></section>
@if (session('status'))<div class="form-success" role="status">{{ session('status') }}</div>@endif
@if ($errors->any())<div class="form-alert" role="alert">{{ $errors->first() }}</div>@endif
<section class="admin-management-card guide-admin-panel">
    <form id="{{ $slug }}-form" method="POST" action="{{ route('admin.pages.update', $slug) }}" class="cavernas-form guide-admin-form" data-article-form>
        @csrf @method('PATCH')
        <div class="form-columns">
            <label for="{{ $slug }}-eyebrow">Eyebrow<input id="{{ $slug }}-eyebrow" name="eyebrow" value="{{ old('eyebrow', $page->eyebrow) }}" maxlength="80" required></label>
            <label for="{{ $slug }}-title">Page title<textarea id="{{ $slug }}-title" name="title" rows="2" maxlength="255" required>{{ old('title', $page->title) }}</textarea></label>
        </div>
        <label for="{{ $slug }}-intro">Introduction<textarea id="{{ $slug }}-intro" name="intro" rows="2" maxlength="1000" required>{{ old('intro', $page->intro) }}</textarea></label>
        <label for="{{ $slug }}-body">Page content</label>
        <textarea id="{{ $slug }}-body" name="body" rows="20" required>{{ old('body', $page->body) }}</textarea>
        <div id="{{ $slug }}-editor" data-article-editor data-upload-url="{{ route('admin.pages.images.store', $slug) }}" aria-label="{{ ucfirst($slug) }} page editor"></div>
        <div class="guide-admin-actions"><a class="text-link" href="{{ route('content.page', $slug) }}">View {{ $slug }} page</a><button class="button button-primary" type="submit">Save {{ $slug }} page</button></div>
    </form>
</section>
@endsection
