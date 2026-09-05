@props(['photo', 'competition'])
<div class="ip-work-preview" x-data="{ failed: false }">
    <strong class="ip-work-code">{{ __('result_selection.work') }} {{ $photo->workCode() }}</strong>
    @if($photo->submission->category->photos_grouped)<span>{{ __('series.identity', ['code' => $photo->submission->seriesCode()]) }}</span>@endif
    @if($photo->jury_sanitized_at && $photo->jury_path && !$photo->withdrawn_at && $photo->submission->status->value === 'approved')
        <a class="ip-work-image" href="{{ route('eys.competitions.results.photos.show', [$competition, $photo]) }}" target="_blank" rel="noopener" x-show="!failed" aria-label="{{ __('result_selection.work').' '.$photo->workCode().' — '.__('result_selection.preview') }}">
            <img src="{{ route('eys.competitions.results.photos.show', [$competition, $photo]) }}" alt="{{ __('result_selection.work').' '.$photo->workCode() }}" loading="lazy" decoding="async" x-on:error="failed = true">
            <span>{{ __('result_selection.preview') }}</span>
        </a>
        <span class="ip-work-unavailable" x-show="failed" x-cloak>{{ __('result_selection.unavailable') }}</span>
    @else
        <span class="ip-work-unavailable">{{ __('result_selection.unavailable') }}</span>
    @endif
</div>
