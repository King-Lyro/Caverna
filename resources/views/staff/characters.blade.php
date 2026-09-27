@extends('layouts.cavernas')

@section('content')
<section class="content-page"><p class="eyebrow">Staff desk</p><h1>Character<br><em>records.</em></h1><div class="activity-table-wrap"><table class="activity-table"><thead><tr><th>Character</th><th>Player</th><th>Allegiance</th><th>Role</th><th>Status</th><th></th></tr></thead><tbody>@foreach ($characters as $character)<tr><td>{{ $character->name }}</td><td>{{ $character->user->name }}</td><td>{{ $character->allegiance }}</td><td>{{ $character->role }}</td><td>{{ $character->status }}</td><td><a class="text-link" href="{{ route('staff.characters.edit', $character) }}">Edit</a></td></tr>@endforeach</tbody></table></div>{{ $characters->links() }}</section>
@endsection
