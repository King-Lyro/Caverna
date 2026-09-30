@extends('layouts.staff')

@section('content')
<section class="page-heading page-heading-theme compact-heading">
    <p class="eyebrow">Administrator panel</p>
    <h1>Users and<br><em>permissions.</em></h1>
    <p>Review registrations, manage account status, and assign more than one role to each member.</p>
</section>
@if (session('status'))<div class="form-success">{{ session('status') }}</div>@endif
@if ($errors->any())<div class="form-alert" role="alert">{{ $errors->first() }}</div>@endif
<form method="GET" class="directory-search admin-user-search">
    <input name="search" value="{{ $search }}" placeholder="Search names or email addresses">
    <button class="button button-primary" type="submit">Search</button>
</form>
<form method="POST" action="{{ route('admin.users.bulk-update') }}" class="admin-bulk-form">@csrf @method('PATCH')
<section class="admin-user-list">
@forelse ($users as $user)
    <article class="admin-user-card">
        <div class="admin-user-summary"><div><p class="eyebrow">{{ $user->status }}</p><h2>{{ $user->name }}</h2><p>{{ $user->email }}</p></div><time>{{ $user->created_at->format('M j, Y') }}</time></div>
        <div class="admin-user-form">
            <label>Status<select name="users[{{ $user->id }}][status]"><option value="pending" @selected($user->status === 'pending')>Pending</option><option value="approved" @selected($user->status === 'approved')>Approved</option><option value="rejected" @selected($user->status === 'rejected')>Rejected</option></select></label>
            <fieldset><legend>Roles</legend>@foreach ($roles as $role)<label class="checkbox-label"><input type="checkbox" name="users[{{ $user->id }}][roles][]" value="{{ $role->id }}" @checked($user->hasRole($role->slug))> {{ $role->name }}</label>@endforeach</fieldset>
        </div>
    </article>
@empty
    <div class="forum-empty"><h2>No users found.</h2><p>Try a different name or email address.</p></div>
@endforelse
</section>
@if ($users->isNotEmpty())<div class="admin-page-save"><button class="button button-primary" type="submit">Save changes</button></div>@endif
</form>
{{ $users->links() }}
@endsection
