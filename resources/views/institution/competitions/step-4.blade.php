<x-institution.app-layout :title="__('institution.nav.competitions')">
    @include('institution.competitions._steps')

    @if ($competition->status->value === 'needs_info' && $competition->latest_review_message)
        <div class="ip-alert ip-alert-warning">
            <x-institution.icon name="warning" />
            <div>
                <div class="ip-alert-title">{{ __('institution.competitions.needs_info_title') }}</div>
                <div class="ip-alert-text">{{ $competition->latest_review_message }}</div>
            </div>
        </div>
    @endif

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

        <div class="ip-form-actions ip-form-actions-sticky">
            <span class="ip-save-meta">{{ __('institution.competitions.last_saved_at', ['time' => $competition->updated_at->format('d.m.Y H:i')]) }}</span>
            <button type="submit" name="action" value="draft" class="ia-btn ia-btn-secondary">{{ __('institution.competitions.save_draft') }}</button>
            <button type="submit" name="action" value="next" class="ia-btn" @disabled($competitionTypes->isEmpty())>{{ __('institution.competitions.next_step') }} →</button>
        </div>
    </form>
</x-institution.app-layout>
