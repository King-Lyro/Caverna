@extends('layouts.staff')

@section('content')
<section class="page-heading page-heading-theme compact-heading"><p class="eyebrow">Staff desk</p><h1>Litter<br><em>review.</em></h1><p>Adjust survival outcomes and disabilities recorded at birth.</p></section>
@if (session('status'))<div class="form-success">{{ session('status') }}</div>@endif
<section class="admin-management-card">
<div class="section-heading"><div><p class="eyebrow">Recent litters</p><h2>Awaiting review.</h2></div></div>
@if ($litters->isNotEmpty())<form method="POST" action="{{ route('staff.litters.bulk-update') }}" class="admin-bulk-form">@csrf @method('PATCH')@endif
@forelse ($litters as $litter)<article class="application-card"><div class="application-heading"><div><h2>{{ $litter->pregnancy->female->name }} & {{ $litter->pregnancy->male->name }}</h2><p>{{ $litter->born_at->format('M j, Y') }} · {{ $litter->surviving_count }}/{{ $litter->kit_count }} surviving</p></div></div>@foreach ($litter->kits as $kit)<div class="kit-review"><span>Kit {{ $loop->iteration }} · {{ $kit->sex }}</span><select name="kits[{{ $kit->id }}][status]"><option value="surviving" @selected($kit->status === 'surviving')>Surviving</option><option value="deceased" @selected($kit->status === 'deceased')>Deceased</option></select><label class="checkbox-label"><input type="hidden" name="kits[{{ $kit->id }}][has_disability]" value="0"><input type="checkbox" name="kits[{{ $kit->id }}][has_disability]" value="1" @checked($kit->has_disability)> Disability</label></div>@endforeach</article>@empty<p class="form-intro">No litters need review.</p>@endforelse
@if ($litters->isNotEmpty())<div class="admin-page-save"><button class="button button-primary" type="submit">Save changes</button></div></form>@endif
{{ $litters->links() }}
</section>
@endsection
