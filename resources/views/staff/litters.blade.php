@extends('layouts.cavernas')

@section('content')
<section class="form-panel staff-panel"><p class="eyebrow">Staff desk</p><h1>Litter<br><em>review.</em></h1>@if (session('status'))<div class="form-success">{{ session('status') }}</div>@endif
@forelse ($litters as $litter)<article class="application-card"><div class="application-heading"><div><h2>{{ $litter->pregnancy->female->name }} & {{ $litter->pregnancy->male->name }}</h2><p>{{ $litter->born_at->format('M j, Y') }} · {{ $litter->surviving_count }}/{{ $litter->kit_count }} surviving</p></div></div>@foreach ($litter->kits as $kit)<form method="POST" action="{{ route('staff.litters.kit', $kit) }}" class="kit-review">@csrf @method('PATCH')<span>Kit {{ $loop->iteration }} · {{ $kit->sex }}</span><select name="status"><option value="surviving" @selected($kit->status === 'surviving')>Surviving</option><option value="deceased" @selected($kit->status === 'deceased')>Deceased</option></select><label><input type="checkbox" name="has_disability" value="1" @checked($kit->has_disability)> Disability</label><button class="text-link" type="submit">Save</button></form>@endforeach</article>@empty<p class="form-intro">No litters need review.</p>@endforelse
{{ $litters->links() }}</section>
@endsection
