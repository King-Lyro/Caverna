@extends('layouts.staff')

@section('content')
<section class="page-heading page-heading-theme compact-heading"><p class="eyebrow">Administrator panel</p><h1>Forum<br><em>boards.</em></h1><p>Shape each path members can write within.</p></section>
@if (session('status'))<div class="form-success">{{ session('status') }}</div>@endif
@if ($errors->any())<div class="form-alert" role="alert">{{ $errors->first() }}</div>@endif
<section class="admin-management-card">
    <div class="section-heading"><div><p class="eyebrow">Existing boards</p><h2>Shape each path.</h2></div></div>
    @php($boards = $categories->flatMap->boards)
    @if ($boards->isNotEmpty())<form method="POST" action="{{ route('admin.content.boards.bulk-update') }}" class="admin-bulk-form">@csrf @method('PATCH')@foreach ($boards as $board)<div class="admin-inline-form admin-board-form"><select name="boards[{{ $board->id }}][forum_category_id]">@foreach ($categories as $option)<option value="{{ $option->id }}" @selected($option->id === $board->forum_category_id)>{{ $option->name }}</option>@endforeach</select><input name="boards[{{ $board->id }}][name]" value="{{ $board->name }}" required><input name="boards[{{ $board->id }}][description]" value="{{ $board->description }}"><input type="number" name="boards[{{ $board->id }}][sort_order]" value="{{ $board->sort_order }}" min="0"><label class="checkbox-label"><input type="hidden" name="boards[{{ $board->id }}][is_ic]" value="0"><input type="checkbox" name="boards[{{ $board->id }}][is_ic]" value="1" @checked($board->is_ic)> IC</label></div>@endforeach<div class="admin-page-save"><button class="button button-primary" type="submit">Save changes</button></div></form>@endif
    <form method="POST" action="{{ route('admin.content.boards.store') }}" class="admin-create-form">@csrf<h3>New board</h3><select name="forum_category_id" required>@foreach ($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select><input name="name" placeholder="Board name" required><input name="description" placeholder="Description"><input type="number" name="sort_order" value="0" min="0"><label class="checkbox-label"><input type="checkbox" name="is_ic" value="1"> In character</label><button class="button button-primary" type="submit">Create</button></form>
</section>
@endsection
