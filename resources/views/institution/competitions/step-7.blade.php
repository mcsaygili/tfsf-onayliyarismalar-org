<x-institution.app-layout :title="__('institution.nav.competitions')">
    @include('institution.competitions._steps')

    <form
        method="POST"
        action="{{ route('institution.competitions.step.update', [$competition, $step]) }}"
        novalidate
        data-wizard-form
        x-data="{
            help: null,
            helpTrigger: null,
            openHelp(title, description, example = '', trigger = null) {
                this.help = { title, description, example }; this.helpTrigger = trigger;
                this.$nextTick(() => { this.$refs.helpDialog?.showModal(); this.$nextTick(() => this.$refs.helpClose?.focus()); });
            },
            closeHelp() {
                if (this.$refs.helpDialog?.open) this.$refs.helpDialog.close();
                this.help = null; this.$nextTick(() => this.helpTrigger?.focus());
            },
        }"
        @keydown.escape.window="closeHelp()"
    >
        @csrf @method('PUT')

        @if ($errors->any())
            <div class="ip-alert ip-alert-warning ip-category-errors"><x-institution.icon name="warning" /><div><div class="ip-alert-title">{{ __('institution.competitions.validation.award_errors_title') }}</div><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>
        @endif

        <div class="ip-card">
            <div class="ip-section-title">{{ __('institution.competitions.category_awards_title') }}</div>
            <div class="ip-section-hint">{{ __('institution.competitions.category_awards_hint') }}</div>

            <div class="ip-award-category-stack">
                @foreach ($categories as $category)
                    <section
                        class="ip-category-block"
                        x-data="{
                            awards: @js($categoryAwardFormData[$category->id]['awards']),
                            addAward() { if (this.awards.length < 50) this.awards.push({ id: '', award_reference_id: '', quantity: 1, tr: { special_award_text: '', material_award: '' }, en: { special_award_text: '', material_award: '' } }); },
                            removeAward(index) { this.awards.splice(index, 1); },
                        }"
                    >
                        <div class="ip-category-heading">
                            <div class="ip-category-toggle">
                                <span class="ip-category-number">{{ $loop->iteration }}</span>
                                <span><strong>{{ $category->name }}</strong><small><span x-text="awards.length"></span> {{ __('institution.competitions.award_count') }}</small></span>
                            </div>
                            <button type="button" class="ia-btn ia-btn-secondary" @click="addAward" :disabled="awards.length >= 50">+ {{ __('institution.competitions.add_award') }}</button>
                        </div>

                        <div class="ip-award-list">
                            <template x-for="(award, awardIndex) in awards" :key="award.id || `new-${awardIndex}`">
                                <article class="ip-award-row" x-data="{ locale: 'tr' }">
                                    <input type="hidden" :name="`categories[{{ $category->id }}][awards][${awardIndex}][id]`" x-model="award.id">
                                    <div class="ip-award-row-header">
                                        <div><span class="ip-award-number" x-text="awardIndex + 1"></span><strong>{{ __('institution.competitions.award') }}</strong></div>
                                        <button type="button" class="ip-category-remove" @click="removeAward(awardIndex)">{{ __('institution.competitions.remove_award') }}</button>
                                    </div>

                                    <div class="ip-grid-2">
                                        <div class="ia-field">
                                            <div class="ip-field-label-row"><label class="ia-label" :for="`award-{{ $category->id }}-${awardIndex}`">{{ __('institution.competitions.fields.award_reference') }}</label><button type="button" class="ip-field-help-button" @click="openHelp(@js(__('institution.competitions.fields.award_reference')), @js(__('institution.competitions.field_help.award_reference.description')), @js(__('institution.competitions.field_help.award_reference.example')), $el)">?</button></div>
                                            <select class="ia-input" :id="`award-{{ $category->id }}-${awardIndex}`" :name="`categories[{{ $category->id }}][awards][${awardIndex}][award_reference_id]`" x-model="award.award_reference_id">
                                                <option value="">{{ __('institution.competitions.select_award') }}</option>
                                                @foreach ($awardReferences as $reference)<option value="{{ $reference->id }}">{{ $reference->name }}</option>@endforeach
                                            </select>
                                        </div>
                                        <div class="ia-field">
                                            <div class="ip-field-label-row"><label class="ia-label" :for="`quantity-{{ $category->id }}-${awardIndex}`">{{ __('institution.competitions.fields.award_quantity') }}</label><button type="button" class="ip-field-help-button" @click="openHelp(@js(__('institution.competitions.fields.award_quantity')), @js(__('institution.competitions.field_help.award_quantity.description')), @js(__('institution.competitions.field_help.award_quantity.example')), $el)">?</button></div>
                                            <input class="ia-input" type="number" min="1" max="999" inputmode="numeric" :id="`quantity-{{ $category->id }}-${awardIndex}`" :name="`categories[{{ $category->id }}][awards][${awardIndex}][quantity]`" x-model.number="award.quantity">
                                        </div>
                                    </div>

                                    @if ($competition->requiresEnglishContent())
                                        <div class="ip-language-tabs" role="tablist">
                                            <button type="button" class="ip-language-tab" :class="locale === 'tr' ? 'is-active' : ''" @click="locale = 'tr'" :aria-selected="(locale === 'tr').toString()">Türkçe</button>
                                            <button type="button" class="ip-language-tab" :class="locale === 'en' ? 'is-active' : ''" @click="locale = 'en'" :aria-selected="(locale === 'en').toString()">English</button>
                                        </div>
                                    @endif

                                    @foreach ($competition->requiresEnglishContent() ? ['tr', 'en'] : ['tr'] as $locale)
                                        <div x-show="locale === @js($locale)" x-cloak class="ip-grid-2 ip-language-panel">
                                            <div class="ia-field">
                                                <div class="ip-field-label-row"><label class="ia-label" :for="`special-{{ $category->id }}-{{ $locale }}-${awardIndex}`">{{ __('institution.competitions.fields.special_award_text') }}</label><button type="button" class="ip-field-help-button" @click="openHelp(@js(__('institution.competitions.fields.special_award_text')), @js(__('institution.competitions.field_help.special_award_text.description')), @js(__('institution.competitions.field_help.special_award_text.example')), $el)">?</button></div>
                                                <input class="ia-input" type="text" maxlength="255" :id="`special-{{ $category->id }}-{{ $locale }}-${awardIndex}`" :name="`categories[{{ $category->id }}][awards][${awardIndex}][{{ $locale }}][special_award_text]`" x-model="award.{{ $locale }}.special_award_text">
                                            </div>
                                            <div class="ia-field">
                                                <div class="ip-field-label-row"><label class="ia-label" :for="`material-{{ $category->id }}-{{ $locale }}-${awardIndex}`">{{ __('institution.competitions.fields.material_award') }}</label><button type="button" class="ip-field-help-button" @click="openHelp(@js(__('institution.competitions.fields.material_award')), @js(__('institution.competitions.field_help.material_award.description')), @js(__('institution.competitions.field_help.material_award.example')), $el)">?</button></div>
                                                <input class="ia-input" type="text" maxlength="255" :id="`material-{{ $category->id }}-{{ $locale }}-${awardIndex}`" :name="`categories[{{ $category->id }}][awards][${awardIndex}][{{ $locale }}][material_award]`" x-model="award.{{ $locale }}.material_award">
                                            </div>
                                        </div>
                                    @endforeach
                                </article>
                            </template>

                            <div class="ip-alert ip-alert-warning" x-show="awards.length === 0" x-cloak>
                                <x-institution.icon name="warning" /><div class="ip-alert-text">{{ __('institution.competitions.category_award_required') }}</div>
                            </div>
                        </div>
                    </section>
                @endforeach
            </div>
        </div>

        <div class="ip-form-actions ip-form-actions-sticky"><span class="ip-save-meta">{{ __('institution.competitions.last_saved_at', ['time' => $competition->updated_at->format('d.m.Y H:i')]) }}</span><button type="submit" name="action" value="draft" class="ia-btn ia-btn-secondary">{{ __('institution.competitions.save_draft') }}</button><button type="submit" name="action" value="next" class="ia-btn">{{ __('institution.competitions.next_step') }} →</button></div>

        <dialog class="ip-field-help-dialog ip-native-dialog" x-ref="helpDialog" @click="if ($event.target === $el) closeHelp()" @cancel.prevent="closeHelp()" :aria-label="help?.title">
            <section class="ip-field-help-panel"><div class="ip-field-help-header"><h2 x-text="help?.title"></h2><button x-ref="helpClose" type="button" class="ip-field-help-close" @click="closeHelp()" aria-label="{{ __('institution.competitions.close_help') }}">×</button></div><div class="ip-field-help-body"><p class="ip-field-help-description" x-text="help?.description"></p><div class="ip-field-help-example" x-show="help?.example"><strong>{{ __('institution.competitions.help_example') }}</strong><span x-text="help?.example"></span></div></div><div class="ip-field-help-footer"><button type="button" class="ia-btn ia-btn-secondary ip-field-help-dismiss" @click="closeHelp()">{{ __('institution.field_help.done') }}</button></div></section>
        </dialog>
    </form>
</x-institution.app-layout>
