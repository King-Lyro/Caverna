@extends('layouts.staff')

@section('content')
<section class="page-heading page-heading-theme compact-heading"><p class="eyebrow">Administrator panel</p><h1>Site<br><em>rules.</em></h1><p>Organize categories and maintain the rules members see.</p></section>
@if (session('status'))<div class="form-success" role="status">{{ session('status') }}</div>@endif
@if ($errors->any())<div class="form-alert" role="alert">{{ $errors->first() }}</div>@endif
@if ($categories->isNotEmpty())
<form method="POST" action="{{ route('admin.rules.bulk-update') }}" class="admin-bulk-form">@csrf @method('PATCH')
<section class="admin-management-card admin-rules-card">
    <div class="section-heading"><div><p class="eyebrow">Categories</p><h2>Organize the rules.</h2></div></div>
    @foreach ($categories as $category)<div class="rule-admin-item"><div class="rule-admin-form"><label>Name<input name="categories[{{ $category->id }}][name]" value="{{ $category->name }}" maxlength="120" required></label><label>Order<input type="number" name="categories[{{ $category->id }}][sort_order]" value="{{ $category->sort_order }}" min="0" required></label><button class="button rule-delete" type="submit" form="delete-category-{{ $category->id }}">Delete category</button></div></div>@endforeach
</section>
<section class="admin-management-card admin-rules-card">
    <div class="section-heading"><div><p class="eyebrow">Entries</p><h2>Rule titles and descriptions.</h2></div></div>
    @foreach ($categories as $category)<h3 class="rule-admin-category">{{ $category->name }}</h3>@foreach ($category->rules as $rule)<div class="rule-admin-item"><div class="rule-admin-form"><label>Category<select name="rules[{{ $rule->id }}][rule_category_id]" required>@foreach ($categories as $option)<option value="{{ $option->id }}" @selected($rule->rule_category_id === $option->id)>{{ $option->name }}</option>@endforeach</select></label><label>Title<input name="rules[{{ $rule->id }}][title]" value="{{ $rule->title }}" maxlength="120" required></label><label>Description<textarea name="rules[{{ $rule->id }}][description]" rows="3" required>{{ $rule->description }}</textarea></label><label>Order<input type="number" name="rules[{{ $rule->id }}][sort_order]" value="{{ $rule->sort_order }}" min="0" required></label><button class="button rule-delete" type="submit" form="delete-rule-{{ $rule->id }}">Delete rule</button></div></div>@endforeach @endforeach
</section>
<div class="admin-page-save"><button class="button button-primary" type="submit">Save changes</button></div>
</form>
@endif

@foreach ($categories as $category)<form id="delete-category-{{ $category->id }}" method="POST" action="{{ route('admin.rules.categories.destroy', $category) }}" onsubmit="return confirm('Delete this category and all of its rules?')" hidden>@csrf @method('DELETE')</form>@foreach ($category->rules as $rule)<form id="delete-rule-{{ $rule->id }}" method="POST" action="{{ route('admin.rules.entries.destroy', $rule) }}" onsubmit="return confirm('Delete this rule?')" hidden>@csrf @method('DELETE')</form>@endforeach @endforeach

<section class="admin-management-grid">
    <article class="admin-management-card"><form method="POST" action="{{ route('admin.rules.categories.store') }}" class="rule-admin-form admin-create-form">@csrf <h3>New category</h3><label>Name<input name="name" value="{{ old('name') }}" maxlength="120" required></label><label>Order<input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0" required></label><button class="button button-primary" type="submit">Add category</button></form></article>
    @if ($categories->isNotEmpty())<article class="admin-management-card"><form method="POST" action="{{ route('admin.rules.entries.store') }}" class="rule-admin-form admin-create-form">@csrf <h3>New rule</h3><label>Category<select name="rule_category_id" required>@foreach ($categories as $category)<option value="{{ $category->id }}" @selected(old('rule_category_id') == $category->id)>{{ $category->name }}</option>@endforeach</select></label><label>Title<input name="title" value="{{ old('title') }}" maxlength="120" required></label><label>Description<textarea name="description" rows="3" required>{{ old('description') }}</textarea></label><label>Order<input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0" required></label><button class="button button-primary" type="submit">Add rule</button></form></article>@endif
</section>
@endsection