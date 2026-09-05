<div class="mp-declaration-fields">
    <input type="hidden" name="upload_submission_id" value="{{ $submission->id }}">
    <p>{{ __('declarations.upload_hint') }}</p>
    @foreach(['title' => 'text', 'location' => 'text', 'taken_on' => 'date'] as $field => $type)
        <label>{{ __('declarations.'.$field) }}<input class="ia-input" type="{{ $type }}" name="declaration[{{ $field }}]" value="{{ old('upload_submission_id') === $submission->id ? old('declaration.'.$field) : '' }}" @if($type === 'text') maxlength="255" @endif></label>
    @endforeach
    @if($submission->category->photo_story_required)<label>{{ __('declarations.story') }}<textarea class="ia-input" name="declaration[story]" rows="3" maxlength="4000">{{ old('upload_submission_id') === $submission->id ? old('declaration.story') : '' }}</textarea></label>@endif
</div>
