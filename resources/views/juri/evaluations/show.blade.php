<x-juri.app-layout :title="__('juri.evaluation.title')">
    <div x-data="juryTags(@js([
        'tags' => $privateTags,
        'selected' => $selectedTag,
        'photoIds' => $photos->pluck('id')->values(),
        'photoGroups' => $photoGroups,
        'baseUrl' => route('juri.tags.store', [$competition, $assignment->category]),
        'errors' => __('juri.tags.errors'),
        'messages' => __('juri.tags.messages'),
    ]))">
    <a class="jp-back-link" href="{{ route('juri.assignments.show', $competition) }}">← {{ __('juri.evaluation.back') }}</a>
    <header class="je-heading"><div><p>{{ $competition->name }}</p><h1>{{ $assignment->category->name }}</h1></div><div><span>{{ __('juri.evaluation.round') }}</span><strong>{{ $round->name }}</strong></div></header>

    @if($errors->any())<div class="jp-readonly-note"><x-juri.icon name="warning" /><p>{{ $errors->first() }}</p></div>@endif
    @if($finalized)<div class="je-notice is-success"><strong>{{ __('juri.evaluation.finalized_title') }}</strong><span>{{ __('juri.evaluation.finalized_hint') }}</span></div>
    @elseif($evaluationLocked || $phase->value !== 'evaluation_open')<div class="je-notice"><strong>{{ __('juri.evaluation.closed_title') }}</strong><span>{{ __('juri.evaluation.closed_hint') }}</span></div>@endif

    @if($assignment->category->photos_grouped)<p class="je-series-hint">{{ __('series.jury_hint') }}</p>@endif
    @include('juri.evaluations.tags')
    <div class="je-empty" x-show="selected && visibleCount === 0" x-cloak><strong>{{ __('juri.tags.no_matches') }}</strong><p>{{ __('juri.tags.no_matches_hint') }}</p><button type="button" class="ia-btn ia-btn-secondary" @click="filter('')">{{ __('juri.tags.all') }}</button></div>
    @if($photos->isEmpty())
        <div class="je-empty"><strong>{{ __('juri.evaluation.empty_title') }}</strong><p>{{ __('juri.evaluation.empty_hint') }}</p></div>
    @else
        <form method="POST" action="{{ route('juri.evaluations.save', [$competition, $assignment->category]) }}" @change="if ($event.target.name.startsWith('scores[')) scoreDirty = true" @submit="scoreDirty = false">@csrf @method('PUT')
            <input type="hidden" name="evaluation_context" value="{{ old('evaluation_context', $evaluationContext) }}">
            <div class="je-photo-list">
                @foreach($photos as $photo)
                    @if($assignment->category->photos_grouped && ($loop->first || $photos[$loop->index - 1]->competition_submission_id !== $photo->competition_submission_id))
                        <header class="je-series-heading" x-show="visible(@js($photo->id))" data-series-code="{{ $photo->submission->seriesCode() }}">
                            <h2>{{ __('series.identity', ['code' => $photo->submission->seriesCode()]) }}</h2>
                            <p>{{ trans_choice('series.photo_count', $photo->submission->photos->count(), ['count' => $photo->submission->photos->count()]) }}</p>
                            @if(filled($photo->submission->category_story))<details><summary>{{ __('declarations.category_story') }}</summary><p>{{ $photo->submission->category_story }}</p></details>@endif
                        </header>
                    @endif
                    <article class="je-photo-row" x-show="visible(@js($photo->id))">
                        <figure><img src="{{ route('juri.evaluations.photos.show', $photo) }}" alt="{{ __('juri.evaluation.work', ['number' => $photo->workCode()]) }}"><figcaption>{{ __('juri.evaluation.work', ['number' => $photo->workCode()]) }}@if($assignment->category->photos_grouped)<span class="je-series-position">{{ __('series.position', ['position' => $photo->submission->photos->search(fn ($item) => $item->id === $photo->id) + 1]) }}</span>@endif</figcaption></figure>
                        <div class="je-score-area">
                            @if($assignment->category->photo_story_required || $assignment->category->category_story_required)
                                <div class="je-work-stories">
                                    @if($assignment->category->photo_story_required && filled($photo->declarationData()['story']))
                                        <details><summary>{{ __('declarations.jury_story') }}</summary><p>{{ $photo->declarationData()['story'] }}</p></details>
                                    @endif
                                    @if(!$assignment->category->photos_grouped && $assignment->category->category_story_required && filled($photo->submission->category_story))
                                        <details><summary>{{ __('declarations.category_story') }}</summary><p>{{ $photo->submission->category_story }}</p></details>
                                    @endif
                                </div>
                            @endif
                            @foreach($assignment->category->evaluationCriteria as $criterion)
                                @php
                                    $current = old("scores.{$photo->id}.{$criterion->id}", $scores->get($photo->id.':'.$criterion->id)?->score);
                                @endphp
                                <fieldset @disabled($finalized || $evaluationLocked || $phase->value !== 'evaluation_open')><legend><strong>{{ $criterion->criterion?->name }}</strong><span>{{ $criterion->min_score }}–{{ $criterion->max_score }}</span></legend><div class="je-score-options">
                                    @for($value=$criterion->min_score;$value<=$criterion->max_score;$value++)<label><input type="radio" name="scores[{{ $photo->id }}][{{ $criterion->id }}]" value="{{ $value }}" @checked((int)$current === $value)><span>{{ $value }}</span></label>@endfor
                                </div></fieldset>
                            @endforeach
                            <div class="je-photo-tags" role="group" aria-label="{{ __('juri.tags.photo_tags') }}" x-cloak>
                                <strong>{{ __('juri.tags.photo_tags') }}</strong>
                                <div class="je-tag-filters">
                                    <template x-for="tag in tags" :key="tag.id">
                                        <button type="button" class="je-tag" :disabled="busy" :aria-pressed="has(tag, @js($photo->id))" @click="toggle(tag, @js($photo->id))">
                                            <span class="je-tag-dot" :style="{ backgroundColor: tag.color }" aria-hidden="true"></span><span x-text="tag.name"></span><span x-show="has(tag, @js($photo->id))">{{ __('juri.tags.added') }}</span>
                                        </button>
                                    </template>
                                </div>
                                <span x-show="tags.length === 0">{{ __('juri.tags.create_first') }}</span>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
            @unless($finalized || $evaluationLocked || $phase->value !== 'evaluation_open')<footer class="je-actions"><button class="ia-btn ia-btn-secondary" type="submit">{{ __('juri.evaluation.save_draft') }}</button><button class="ia-btn" type="submit" formaction="{{ route('juri.evaluations.finalize', [$competition, $assignment->category]) }}" formmethod="POST" onclick="return confirm(@js(__('juri.evaluation.finalize_confirm')))">{{ __('juri.evaluation.finalize') }}</button></footer>@endunless
        </form>
    @endif
    </div>
</x-juri.app-layout>
