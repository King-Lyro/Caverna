@extends('layouts.cavernas')

@section('content')
<section class="content-page activity-page">
    <div class="page-heading page-heading-theme">
        <p class="eyebrow">{{ __('site.activity.eyebrow') }}</p>
        <h1>{!! nl2br(e(__('site.activity.title'))) !!}</h1>
        <p>{{ __('site.activity.intro') }}</p>
    </div>
    <div class="activity-summary">
        <div class="activity-summary-card"><span class="eyebrow">{{ __('site.activity.active_count') }}</span><strong>{{ $characters->total() }}</strong><small>{{ __('site.activity.active_count_help') }}</small></div>
        <div class="activity-summary-card activity-summary-card-accent"><span class="eyebrow">{{ __('site.activity.cutoff') }}</span><strong>{{ $cutoff->format('M j') }}</strong><small>{{ __('site.activity.cutoff_help') }}</small></div>
    </div>
    <div class="activity-table-wrap">
        <table class="activity-table">
            <thead><tr><th>{{ __('site.activity.columns.character') }}</th><th>{{ __('site.activity.columns.player') }}</th><th>{{ __('site.activity.columns.allegiance') }}</th><th>{{ __('site.activity.columns.last_post') }}</th><th>{{ __('site.activity.columns.joined') }}</th></tr></thead>
            <tbody>
            @forelse ($characters as $character)
                <tr><td><a href="{{ route('characters.show', $character) }}">{{ $character->name }}</a></td><td>{{ $character->user->name }}</td><td>{{ $character->allegiance }}</td><td>{{ $character->last_ic_post_at?->format('M j, Y') ?? __('site.activity.never') }}</td><td>{{ $character->created_at->format('M j, Y') }}</td></tr>
            @empty
                <tr><td colspan="5">{{ __('site.activity.empty') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $characters->links() }}
</section>
@endsection
