@extends('layouts.cavernas')

@section('content')
<section class="content-page activity-page">
    <x-presentation-heading :eyebrow="__('site.activity.eyebrow')" :title="__('site.activity.title')" :intro="__('site.activity.intro')" />
    <div class="activity-list-content">
        <section class="activity-how" aria-labelledby="activity-how-title">
            <p class="eyebrow">Activity checks</p><h2 id="activity-how-title">How it works</h2>
            <ul>
                <li>This list shows active characters without an in-character post in the last 30 days.</li>
                <li>Each week without an IC post costs 20 energy. At zero energy, a character becomes inactive for up to two weeks before dying.</li>
                <li>An IC post restores 10 energy at home and costs 10 energy in another territory. Home posts can reactivate an inactive character.</li>
            </ul>
            <p class="activity-cutoff">{{ __('site.activity.cutoff') }} <strong>{{ $cutoff->format('M j, Y') }}</strong></p>
        </section>
        <section class="activity-list-section" aria-labelledby="activity-list-title">
        <div class="activity-list-heading"><h2 id="activity-list-title">The list</h2><span>{{ $characters->total() }} {{ $characters->total() === 1 ? 'character' : 'characters' }}</span></div>
        <p class="activity-list-note">A qualifying IC post moves a character off this list.</p>
        <div class="activity-table-wrap">
        <table class="activity-table">
            <thead><tr><th scope="col">ID</th><th scope="col">{{ __('site.activity.columns.character') }}</th><th scope="col">{{ __('site.activity.columns.player') }}</th><th scope="col">{{ __('site.activity.columns.last_post') }}</th><th scope="col">{{ __('site.activity.columns.joined') }}</th></tr></thead>
            <tbody>
            @forelse ($characters as $character)
                <tr><td>{{ $character->id }}</td><td><a href="{{ route('characters.show', $character) }}">{{ $character->name }}</a></td><td><x-member-link :member="$character->user" /></td><td>{{ $character->last_ic_post_at?->format('M j, Y') ?? __('site.activity.never') }}</td><td>{{ $character->created_at->format('M j, Y') }}</td></tr>
            @empty
                <tr><td colspan="5"><div class="activity-empty">{{ __('site.activity.empty') }}</div></td></tr>
            @endforelse
            </tbody>
        </table>
        </div></section>
    </div>
    {{ $characters->links() }}
</section>
@endsection
