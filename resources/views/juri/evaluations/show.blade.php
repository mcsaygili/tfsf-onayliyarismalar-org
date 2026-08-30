<x-juri.app-layout :title="__('juri.evaluation.title')">
    <a class="jp-back-link" href="{{ route('juri.assignments.show', $competition) }}">← {{ __('juri.evaluation.back') }}</a>
    <header class="je-heading"><div><p>{{ $competition->name }}</p><h1>{{ $assignment->category->name }}</h1></div><div><span>{{ __('juri.evaluation.round') }}</span><strong>{{ $round->name }}</strong></div></header>

    @if($errors->any())<div class="jp-readonly-note"><x-juri.icon name="warning" /><p>{{ $errors->first() }}</p></div>@endif
    @if($finalized)<div class="je-notice is-success"><strong>{{ __('juri.evaluation.finalized_title') }}</strong><span>{{ __('juri.evaluation.finalized_hint') }}</span></div>
    @elseif($phase->value !== 'evaluation_open')<div class="je-notice"><strong>{{ __('juri.evaluation.closed_title') }}</strong><span>{{ __('juri.evaluation.closed_hint') }}</span></div>@endif

    @if($photos->isEmpty())
        <div class="je-empty"><strong>{{ __('juri.evaluation.empty_title') }}</strong><p>{{ __('juri.evaluation.empty_hint') }}</p></div>
    @else
        <form method="POST" action="{{ route('juri.evaluations.save', [$competition, $assignment->category]) }}">@csrf @method('PUT')
            <div class="je-photo-list">
                @foreach($photos as $photo)
                    <article class="je-photo-row">
                        <figure><img src="{{ route('juri.evaluations.photos.show', $photo) }}" alt="{{ __('juri.evaluation.work', ['number' => str_pad((string)$loop->iteration, 3, '0', STR_PAD_LEFT)]) }}"><figcaption>{{ __('juri.evaluation.work', ['number' => str_pad((string)$loop->iteration, 3, '0', STR_PAD_LEFT)]) }}</figcaption></figure>
                        <div class="je-score-area">
                            @foreach($assignment->category->evaluationCriteria as $criterion)
                                @php
                                    $current = old("scores.{$photo->id}.{$criterion->id}", $scores->get($photo->id.':'.$criterion->id)?->score);
                                @endphp
                                <fieldset @disabled($finalized || $phase->value !== 'evaluation_open')><legend><strong>{{ $criterion->criterion?->name }}</strong><span>{{ $criterion->min_score }}–{{ $criterion->max_score }}</span></legend><div class="je-score-options">
                                    @for($value=$criterion->min_score;$value<=$criterion->max_score;$value++)<label><input type="radio" name="scores[{{ $photo->id }}][{{ $criterion->id }}]" value="{{ $value }}" @checked((int)$current === $value)><span>{{ $value }}</span></label>@endfor
                                </div></fieldset>
                            @endforeach
                        </div>
                    </article>
                @endforeach
            </div>
            @unless($finalized || $phase->value !== 'evaluation_open')<footer class="je-actions"><button class="ia-btn ia-btn-secondary" type="submit">{{ __('juri.evaluation.save_draft') }}</button><button class="ia-btn" type="submit" formaction="{{ route('juri.evaluations.finalize', [$competition, $assignment->category]) }}" formmethod="POST" onclick="return confirm(@js(__('juri.evaluation.finalize_confirm')))">{{ __('juri.evaluation.finalize') }}</button></footer>@endunless
        </form>
    @endif
</x-juri.app-layout>
