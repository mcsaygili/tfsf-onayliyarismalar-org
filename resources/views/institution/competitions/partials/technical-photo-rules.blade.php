<fieldset class="ip-category-section" data-technical-photo-rules>
    <legend class="ia-label">{{ __('photo_rules.title') }}</legend>
    <p class="ip-section-hint">{{ __('photo_rules.hint') }} {{ __('photo_rules.server_limit', ['value' => config('competition-photos.max_file_size_mb')]) }}</p>
    <fieldset class="ia-field">
        <legend class="ia-label">{{ __('photo_rules.fields.formats') }}</legend>
        <div class="ip-reference-options">
            @foreach (\App\Support\Photo\CategoryPhotoRules::FORMATS as $format)
                <label class="ip-reference-option"><input type="checkbox" :name="`categories[${index}][photo_rules][formats][]`" value="{{ $format }}" x-model="category.photo_rules.formats"><span>{{ strtoupper($format) }}</span></label>
            @endforeach
        </div>
    </fieldset>
    <div class="ip-reference-options">
        @foreach (\App\Support\Photo\CategoryPhotoRules::LIMITS as $field)
            @php($isSize = str_ends_with($field, '_mb'))
            <div class="ia-field">
                <label class="ia-label" :for="`category-${index}-{{ $field }}`">{{ __('photo_rules.fields.'.$field) }}</label>
                <input class="ia-input" type="number" min="0" max="{{ $isSize ? config('competition-photos.max_file_size_mb') : 100000 }}" step="{{ $isSize ? '0.001' : '1' }}" :id="`category-${index}-{{ $field }}`" :name="`categories[${index}][photo_rules][{{ $field }}]`" x-model="category.photo_rules.{{ $field }}">
            </div>
        @endforeach
    </div>
    <p class="ip-section-hint">{{ __('photo_rules.dpi_hint') }}</p>
</fieldset>
