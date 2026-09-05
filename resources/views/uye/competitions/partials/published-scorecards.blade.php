<section class="mp-category-section" id="member-result-archive">
    <header><h2>{{ __('result_archive.my_results') }}</h2><p>{{ $archive['name'] }}</p><p>{{ __('result_archive.archive_hint') }}</p></header>
    @if(!$archive['publication'])
        <p class="mp-callout is-warning" role="status">{{ __('result_archive.unavailable') }}</p>
    @elseif(!$archive['has_record'])
        <p class="mp-callout is-warning" role="status">{{ __('result_archive.missing') }}</p>
    @else
        <p>{{ __('result_archive.version', ['version' => $archive['publication']->version]) }} · {{ $archive['publication']->published_at->format('d.m.Y H:i') }}</p>
        <div class="mp-archive-works">
        @foreach($archive['photos'] as $photo)
            <article class="mp-archive-work">
                <div class="mp-archive-image" x-data="{ failed: false }">
                    @if($photo['image_url'])<a href="{{ $photo['image_url'] }}" target="_blank" rel="noopener" x-show="!failed"><img src="{{ $photo['image_url'] }}" alt="{{ $photo['work_code'] }}" loading="lazy" x-on:error="failed = true"></a><p x-show="failed" x-cloak>{{ __('result_archive.photo_missing') }}</p>@else<p>{{ __('result_archive.photo_missing') }}</p>@endif
                </div>
                <div class="mp-archive-description">
                    @if($photo['series_code'] ?? null)<p><strong>{{ __('series.identity', ['code' => $photo['series_code']]) }}</strong></p>@endif
                    <h3>{{ $photo['work_code'] }} · {{ $photo['category_name'] }}</h3>
                    @if($photo['declaration']['title'] ?? null)<p>{{ $photo['declaration']['title'] }}</p>@endif
                    <p>{{ $photo['capture_device_name'] }}</p>
                    @if($photo['declaration']['location'] ?? null)<p>{{ __('declarations.location') }}: {{ $photo['declaration']['location'] }}</p>@endif
                    @if($photo['declaration']['taken_on'] ?? null)<p>{{ __('declarations.taken_on') }}: {{ $photo['declaration']['taken_on'] }}</p>@endif
                    @if($photo['result'])<p><strong>{{ __('result_archive.official_result') }}: #{{ $photo['result']['rank'] }}</strong> · {{ __('uye.competitions.result_score', ['score' => $photo['result']['average_score']]) }}</p>@if($photo['award_names'])<p>{{ $photo['award_names'] }}</p>@endif
                    @else<p>{{ __('result_archive.no_final_result') }}</p>@endif
                    @forelse($photo['scorecards'] as $scorecard)
                        <div class="mp-scorecard"><div class="mp-archive-score-heading"><strong>{{ __('uye.competitions.scorecard_title', ['round' => $scorecard['round_number']]) }}</strong><span>{{ __('uye.competitions.scorecard_average', ['score' => number_format($scorecard['average'], 2, app()->getLocale() === 'tr' ? ',' : '.', app()->getLocale() === 'tr' ? '.' : ',')]) }}</span></div><div class="mp-scorecard-values">@foreach($scorecard['scores'] as $score)<span>{{ $score['label'] }} <b>{{ $score['score'] }}</b></span>@endforeach</div><small>{{ __('uye.competitions.scorecard_privacy') }}</small></div>
                    @empty<p>{{ __('result_archive.no_scores') }}</p>@endforelse
                    @if($photo['declaration']['story'] ?? null)<details><summary>{{ __('declarations.story') }}</summary><p>{{ $photo['declaration']['story'] }}</p></details>@endif
                    @if($photo['category_story'])<details><summary>{{ __('declarations.category_story') }}</summary><p>{{ $photo['category_story'] }}</p></details>@endif
                </div>
            </article>
        @endforeach
        </div>
    @endif
</section>
