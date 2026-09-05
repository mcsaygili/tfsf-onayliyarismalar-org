<x-institution.app-layout :title="__('institution.nav.competitions')">
    @include('institution.competitions._steps')

    <form method="POST" action="{{ route('institution.competitions.step.update', [$competition, $step]) }}" novalidate autocomplete="off" data-wizard-form
          x-data="{ type: @js(old('competition_type', $competition->competition_type_id)), initialType: @js($competition->competition_type_id) }">
        @csrf
        @method('PUT')

        <div class="ip-card">
            <div class="ip-section-title">{{ __('institution.competitions.steps.4.label') }}</div>
            <div class="ip-section-hint">{{ __('institution.competitions.steps.4.hint') }}</div>

            <fieldset class="ia-field ip-field-last">
                <legend class="ip-visually-hidden">{{ __('institution.competitions.fields.competition_type') }}</legend>

                <x-institution.field-label
                    for="competition_type"
                    :group="true"
                    :value="__('institution.competitions.fields.competition_type')"
                    :description="__('institution.competitions.field_help.competition_type.description')"
                    :example="__('institution.competitions.field_help.competition_type.example')"
                />

                @if ($competitionTypes->isEmpty())
                    <div class="ip-alert ip-alert-warning ip-alert-last">
                        <x-institution.icon name="warning" />
                        <div class="ip-alert-text">{{ __('institution.competitions.no_competition_types') }}</div>
                    </div>
                @else
                    <div class="ip-choice-options">
                        @foreach ($competitionTypes as $competitionType)
                            <label class="ip-choice-option" for="competition_type_{{ $competitionType->id }}">
                                <input
                                    id="competition_type_{{ $competitionType->id }}"
                                    type="radio"
                                    name="competition_type"
                                    value="{{ $competitionType->id }}"
                                    x-model="type"
                                    @checked(old('competition_type', $competition->competition_type_id) === $competitionType->id)
                                    @if ($errors->has('competition_type')) aria-invalid="true" aria-describedby="competition-type-error" @endif
                                >
                                <span class="ip-choice-content">
                                    <span class="ip-choice-heading">
                                        <strong>{{ $competitionType->name }}</strong>
                                    </span>
                                    <span class="ip-choice-description">{{ $competitionType->description }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                @endif

                <x-institution.input-error id="competition-type-error" :messages="$errors->get('competition_type')" />
            </fieldset>
        </div>

        <div class="ip-alert ip-alert-warning" x-show="initialType && type !== initialType" x-cloak role="status">
            <x-institution.icon name="warning" />
            <div class="ip-alert-text">{{ __('institution.competitions.competition_type_change_warning') }}</div>
        </div>

        <section class="ip-card">
            <h2>{{ __('registration.heading') }}</h2><p>{{ __('registration.settings_hint') }}</p>
            <input type="hidden" name="registration_required" value="0">
            <label><input type="checkbox" name="registration_required" value="1" @checked(old('registration_required', $competition->registration_required))> {{ __('registration.required') }}</label>
            <div class="ia-field"><label for="registration_document_min">{{ __('registration.minimum') }}</label><select class="ia-input" id="registration_document_min" name="registration_document_min">@foreach(range(0, 3) as $count)<option value="{{ $count }}" @selected((int) old('registration_document_min', $competition->registration_document_min) === $count)>{{ $count }}</option>@endforeach</select></div>
            <div class="ia-field"><label for="registration_reviewer">{{ __('registration.reviewer') }}</label><select class="ia-input" id="registration_reviewer" name="registration_reviewer">@foreach(['institution', 'representative'] as $reviewer)<option value="{{ $reviewer }}" @selected(old('registration_reviewer', $competition->registration_reviewer) === $reviewer)>{{ __('registration.'.$reviewer) }}</option>@endforeach</select></div>
            @foreach(['registration', 'registration_required', 'registration_document_min', 'registration_reviewer'] as $field)<x-institution.input-error :messages="$errors->get($field)" />@endforeach
        </section>

        <div class="ip-form-actions ip-form-actions-sticky">
            <span class="ip-save-meta">{{ __('institution.competitions.last_saved_at', ['time' => $competition->updated_at->format('d.m.Y H:i')]) }}</span>
            <button type="submit" name="action" value="draft" class="ia-btn ia-btn-secondary">{{ __('institution.competitions.save_draft') }}</button>
            <button type="submit" name="action" value="next" class="ia-btn" @disabled($competitionTypes->isEmpty())>{{ __('institution.competitions.next_step') }} →</button>
        </div>
    </form>
</x-institution.app-layout>
