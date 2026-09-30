@extends('layouts.staff')

@section('content')
<section class="page-heading page-heading-theme compact-heading"><p class="eyebrow">Administrator panel</p><h1>Forum<br><em>categories.</em></h1><p>Create and order the top-level groupings boards live in.</p></section>
@if (session('status'))<div class="form-success">{{ session('status') }}</div>@endif
@if ($errors->any())<div class="form-alert" role="alert">{{ $errors->first() }}</div>@endif
<section class="admin-management-card">
    <div class="section-heading"><div><p class="eyebrow">Existing categories</p><h2>Organize the world.</h2></div></div>
    @if ($categories->isNotEmpty())<form method="POST" action="{{ route('admin.content.categories.bulk-update') }}" class="admin-bulk-form">@csrf @method('PATCH')@foreach ($categories as $category)<div class="admin-inline-form"><input name="categories[{{ $category->id }}][name]" value="{{ $category->name }}" required><input name="categories[{{ $category->id }}][description]" value="{{ $category->description }}"><input type="number" name="categories[{{ $category->id }}][sort_order]" value="{{ $category->sort_order }}" min="0"></div>@endforeach<div class="admin-page-save"><button class="button button-primary" type="submit">Save changes</button></div></form>@endif
    <form method="POST" action="{{ route('admin.content.categories.store') }}" class="admin-create-form">@csrf<h3>New category</h3><input name="name" placeholder="Category name" required><input name="description" placeholder="Description"><input type="number" name="sort_order" value="0" min="0"><button class="button button-primary" type="submit">Create</button></form>
</section>
@endsection
