@php
    $useOldDetails = old('details_submission_id') === $submission->id;
    $oldPhotos = $useOldDetails && is_array(old('photos')) ? collect(old('photos'))->filter(fn ($item) => is_array($item) && is_string($item['id'] ?? null))->keyBy('id') : collect();
@endphp
@if($activePhotos->isNotEmpty())
<form method="POST" action="{{ route('competitions.submission.details.update', $submission) }}" class="mp-declarations" data-submission-details>
    @csrf @method('PUT')
    <input type="hidden" name="details_submission_id" value="{{ $submission->id }}">
    <input type="hidden" name="details_version" value="{{ $useOldDetails ? old('details_version', $submission->details_version) : $submission->details_version }}">
    <h3>{{ __('declarations.heading') }}</h3>
    @if($canModifyPhotos)<p>{{ __('declarations.hint') }}</p>@endif
    <p>{{ __('declarations.date_hint') }}</p>
    @if($submission->category->photos_grouped || $submission->category->photo_story_required || $submission->category->category_story_required)<p>{{ __('declarations.privacy_hint') }}</p>@endif
    <fieldset @disabled(!$canModifyPhotos)>
        @if($submission->category->photos_grouped || $submission->category->category_story_required || filled($submission->category_story))
            <label for="category-story-{{ $submission->id }}">{{ __('declarations.category_story') }} · {{ $submission->category->category_story_required ? __('declarations.required') : __('declarations.optional') }}</label>
            <textarea class="ia-input" id="category-story-{{ $submission->id }}" name="category_story" rows="4" maxlength="4000">{{ $useOldDetails ? old('category_story', $submission->category_story) : $submission->category_story }}</textarea>
        @endif
        @foreach($activePhotos->values() as $index => $photo)
            @php($declaration = array_replace($photo->declarationData(), $oldPhotos->get($photo->id, [])))
            <fieldset class="mp-declaration-photo">
                <legend>{{ __('uye.competitions.work_number', ['number' => $index + 1]) }}</legend>
                <input type="hidden" name="photos[{{ $index }}][id]" value="{{ $photo->id }}">
                <div class="mp-declaration-grid">
                    @foreach(['title' => 'text', 'location' => 'text', 'taken_on' => 'date'] as $field => $type)
                        <label for="declaration-{{ $photo->id }}-{{ $field }}">{{ __('declarations.'.$field) }}
                            <input class="ia-input" id="declaration-{{ $photo->id }}-{{ $field }}" type="{{ $type }}" name="photos[{{ $index }}][{{ $field }}]" value="{{ $declaration[$field] ?? '' }}" @if($type === 'text') maxlength="255" @endif>
                        </label>
                    @endforeach
                    @if($submission->category->requiresPhotoOrder())
                        <label for="declaration-{{ $photo->id }}-position">{{ __('declarations.position') }}
                            <input class="ia-input" id="declaration-{{ $photo->id }}-position" type="number" min="1" max="{{ $activePhotos->count() }}" step="1" name="photos[{{ $index }}][position]" value="{{ $declaration['position'] ?? $index + 1 }}">
                        </label>
                    @endif
                </div>
                @if($submission->category->photo_story_required || filled($declaration['story']))
                    <label for="declaration-{{ $photo->id }}-story">{{ __('declarations.story') }} · {{ $submission->category->photo_story_required ? __('declarations.required') : __('declarations.optional') }}</label>
                    <textarea class="ia-input" id="declaration-{{ $photo->id }}-story" name="photos[{{ $index }}][story]" rows="4" maxlength="4000">{{ $declaration['story'] ?? '' }}</textarea>
                @endif
            </fieldset>
        @endforeach
        @if($canModifyPhotos)<button class="ia-btn ia-btn-secondary" type="submit">{{ __('declarations.save') }}</button>@endif
    </fieldset>
</form>
@endif
