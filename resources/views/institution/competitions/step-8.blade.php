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
            <div class="ip-alert ip-alert-warning ip-category-errors">
                <x-institution.icon name="warning" />
                <div>
                    <div class="ip-alert-title">{{ __('institution.competitions.validation.jury_errors_title') }}</div>
                    <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            </div>
        @endif

        <div class="ip-card">
            <div class="ip-section-title">{{ __('institution.competitions.jury_configuration_title') }}</div>
            <div class="ip-section-hint">{{ __('institution.competitions.jury_configuration_hint') }}</div>

            <div class="ip-jury-flow-note">
                <span class="ip-jury-flow-step">1</span><span>{{ __('institution.competitions.jury_flow_search') }}</span>
                <span class="ip-jury-flow-arrow" aria-hidden="true">→</span>
                <span class="ip-jury-flow-step">2</span><span>{{ __('institution.competitions.jury_flow_invite') }}</span>
                <span class="ip-jury-flow-arrow" aria-hidden="true">→</span>
                <span class="ip-jury-flow-step">3</span><span>{{ __('institution.competitions.jury_flow_send') }}</span>
            </div>

            <div class="ip-jury-category-stack">
                @foreach ($categories as $category)
                    <section
                        class="ip-category-block"
                        x-data="{
                            jurors: @js($categoryJurorFormData[$category->id]['jurors']),
                            query: '',
                            results: [],
                            searching: false,
                            searched: false,
                            searchTimer: null,
                            invite: { first_name: '', last_name: '', email: '', locale: 'tr' },
                            inviteError: '',
                            async searchJurors() {
                                window.clearTimeout(this.searchTimer);
                                this.results = []; this.searched = false;
                                if (this.query.trim().length < 2) return;
                                this.searching = true;
                                this.searchTimer = window.setTimeout(async () => {
                                    try {
                                        const response = await fetch(@js(route('institution.competitions.jurors.search', $competition)) + '?q=' + encodeURIComponent(this.query.trim()), { headers: { Accept: 'application/json' } });
                                        if (!response.ok) throw new Error();
                                        this.results = (await response.json()).filter(item => !this.jurors.some(juror => juror.juror_id === item.id));
                                    } catch (_) { this.results = []; }
                                    finally { this.searching = false; this.searched = true; }
                                }, 300);
                            },
                            addExisting(result) {
                                if (this.jurors.length >= 15 || this.jurors.some(juror => juror.juror_id === result.id)) return;
                                const parts = result.name.trim().split(/\s+/);
                                this.jurors.push({ assignment_id: '', type: 'existing', juror_id: result.id, invitation_id: '', first_name: parts.shift() || '', last_name: parts.join(' '), email: result.email, locale: 'tr', status: 'registered' });
                                this.query = ''; this.results = []; this.searched = false;
                            },
                            addInvitation() {
                                this.inviteError = '';
                                const email = this.invite.email.trim().toLowerCase();
                                if (!this.invite.first_name.trim() || !this.invite.last_name.trim() || !/^\S+@\S+\.\S+$/.test(email)) {
                                    this.inviteError = @js(__('institution.competitions.jury_invite_fields_required')); return;
                                }
                                if (this.jurors.some(juror => juror.email.toLowerCase() === email)) {
                                    this.inviteError = @js(__('institution.competitions.jury_duplicate_email')); return;
                                }
                                if (this.jurors.length >= 15) return;
                                this.jurors.push({ assignment_id: '', type: 'invitation', juror_id: '', invitation_id: '', first_name: this.invite.first_name.trim(), last_name: this.invite.last_name.trim(), email, locale: this.invite.locale, status: 'draft' });
                                this.invite = { first_name: '', last_name: '', email: '', locale: 'tr' };
                            },
                            removeJuror(index) { this.jurors.splice(index, 1); },
                        }"
                    >
                        <div class="ip-category-heading">
                            <div class="ip-category-toggle">
                                <span class="ip-category-number">{{ $loop->iteration }}</span>
                                <span>
                                    <strong>{{ $category->name }}</strong>
                                    <small><span x-text="jurors.length"></span> {{ __('institution.competitions.juror_count') }}</small>
                                </span>
                            </div>
                            <span class="ip-jury-capacity" x-text="`${jurors.length}/15`"></span>
                        </div>

                        <div class="ip-jury-picker">
                            <section class="ip-jury-path" aria-labelledby="existing-juror-heading-{{ $category->id }}">
                                <div class="ip-jury-path-heading">
                                    <span class="ip-jury-path-number">A</span>
                                    <div><h3 id="existing-juror-heading-{{ $category->id }}">{{ __('institution.competitions.registered_juror_title') }}</h3><p>{{ __('institution.competitions.registered_juror_hint') }}</p></div>
                                </div>
                                <div class="ia-field ip-jury-search-field">
                                    <div class="ip-field-label-row">
                                        <label class="ia-label" for="juror-search-{{ $category->id }}">{{ __('institution.competitions.fields.juror_search') }}</label>
                                        <button type="button" class="ip-field-help-button" @click="openHelp(@js(__('institution.competitions.fields.juror_search')), @js(__('institution.competitions.field_help.juror_search.description')), @js(__('institution.competitions.field_help.juror_search.example')), $el)">?</button>
                                    </div>
                                    <input class="ia-input" id="juror-search-{{ $category->id }}" type="search" x-model="query" @input="searchJurors" autocomplete="off" :aria-expanded="(results.length > 0).toString()" aria-autocomplete="list" placeholder="{{ __('institution.competitions.juror_search_placeholder') }}">
                                    <div class="ip-jury-search-status" aria-live="polite">
                                        <span x-show="query.trim().length === 1">{{ __('institution.competitions.juror_search_minimum') }}</span>
                                        <span x-show="searching">{{ __('institution.competitions.juror_searching') }}</span>
                                        <span x-show="searched && !searching && results.length === 0">{{ __('institution.competitions.juror_search_empty') }}</span>
                                    </div>
                                    <div class="ip-jury-results" x-show="results.length > 0" x-cloak role="listbox">
                                        <template x-for="result in results" :key="result.id">
                                            <button type="button" class="ip-jury-result" @click="addExisting(result)" role="option">
                                                <span class="ip-juror-avatar" x-text="result.name.split(' ').map(part => part[0]).slice(0, 2).join('').toUpperCase()"></span>
                                                <span><strong x-text="result.name"></strong><small x-text="result.email"></small></span>
                                                <span class="ip-jury-add-label">+ {{ __('institution.competitions.add_juror') }}</span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </section>

                            <section class="ip-jury-path" aria-labelledby="invite-juror-heading-{{ $category->id }}">
                                <div class="ip-jury-path-heading">
                                    <span class="ip-jury-path-number">B</span>
                                    <div><h3 id="invite-juror-heading-{{ $category->id }}">{{ __('institution.competitions.invite_juror_title') }}</h3><p>{{ __('institution.competitions.invite_juror_hint') }}</p></div>
                                </div>
                                <div class="ip-grid-2 ip-jury-invite-grid">
                                    @foreach (['first_name', 'last_name', 'email'] as $inviteField)
                                        <div class="ia-field {{ $inviteField === 'email' ? 'ip-jury-email-field' : '' }}">
                                            <div class="ip-field-label-row">
                                                <label class="ia-label" for="invite-{{ $inviteField }}-{{ $category->id }}">{{ __('institution.competitions.fields.juror_'.$inviteField) }}</label>
                                                <button type="button" class="ip-field-help-button" @click="openHelp(@js(__('institution.competitions.fields.juror_'.$inviteField)), @js(__('institution.competitions.field_help.juror_'.$inviteField.'.description')), @js(__('institution.competitions.field_help.juror_'.$inviteField.'.example')), $el)">?</button>
                                            </div>
                                            <input class="ia-input" id="invite-{{ $inviteField }}-{{ $category->id }}" type="{{ $inviteField === 'email' ? 'email' : 'text' }}" maxlength="255" x-model="invite.{{ $inviteField }}" autocomplete="{{ ['first_name' => 'given-name', 'last_name' => 'family-name', 'email' => 'email'][$inviteField] }}">
                                        </div>
                                    @endforeach
                                    <div class="ia-field">
                                        <div class="ip-field-label-row">
                                            <label class="ia-label" for="invite-locale-{{ $category->id }}">{{ __('institution.competitions.fields.juror_invitation_language') }}</label>
                                            <button type="button" class="ip-field-help-button" @click="openHelp(@js(__('institution.competitions.fields.juror_invitation_language')), @js(__('institution.competitions.field_help.juror_invitation_language.description')), @js(__('institution.competitions.field_help.juror_invitation_language.example')), $el)">?</button>
                                        </div>
                                        <select class="ia-input" id="invite-locale-{{ $category->id }}" x-model="invite.locale"><option value="tr">Türkçe</option><option value="en">English</option></select>
                                    </div>
                                </div>
                                <p class="ip-jury-inline-error" x-show="inviteError" x-cloak x-text="inviteError" role="alert"></p>
                                <button type="button" class="ia-btn ia-btn-secondary ip-jury-invite-button" @click="addInvitation" :disabled="jurors.length >= 15">+ {{ __('institution.competitions.add_invitation') }}</button>
                            </section>
                        </div>

                        <div class="ip-juror-list">
                            <div class="ip-juror-list-heading"><h3>{{ __('institution.competitions.assigned_jurors_title') }}</h3><span x-text="jurors.length"></span></div>
                            <template x-for="(juror, jurorIndex) in jurors" :key="juror.assignment_id || juror.juror_id || `${juror.email}-${jurorIndex}`">
                                <article class="ip-juror-row">
                                    <input type="hidden" :name="`categories[{{ $category->id }}][jurors][${jurorIndex}][assignment_id]`" x-model="juror.assignment_id">
                                    <input type="hidden" :name="`categories[{{ $category->id }}][jurors][${jurorIndex}][type]`" x-model="juror.type">
                                    <input type="hidden" :name="`categories[{{ $category->id }}][jurors][${jurorIndex}][juror_id]`" x-model="juror.juror_id">
                                    <input type="hidden" :name="`categories[{{ $category->id }}][jurors][${jurorIndex}][invitation_id]`" x-model="juror.invitation_id">
                                    <input type="hidden" :name="`categories[{{ $category->id }}][jurors][${jurorIndex}][first_name]`" x-model="juror.first_name">
                                    <input type="hidden" :name="`categories[{{ $category->id }}][jurors][${jurorIndex}][last_name]`" x-model="juror.last_name">
                                    <input type="hidden" :name="`categories[{{ $category->id }}][jurors][${jurorIndex}][email]`" x-model="juror.email">
                                    <input type="hidden" :name="`categories[{{ $category->id }}][jurors][${jurorIndex}][locale]`" x-model="juror.locale">
                                    <input type="hidden" :name="`categories[{{ $category->id }}][jurors][${jurorIndex}][status]`" x-model="juror.status">

                                    <span class="ip-juror-avatar" x-text="`${juror.first_name?.[0] || ''}${juror.last_name?.[0] || ''}`.toUpperCase()"></span>
                                    <span class="ip-juror-identity"><strong x-text="`${juror.first_name} ${juror.last_name}`"></strong><small x-text="juror.email"></small></span>
                                    <span class="ip-badge is-active" x-show="juror.status === 'registered'">{{ __('institution.competitions.jury_status_registered') }}</span>
                                    <span class="ip-badge is-draft" x-show="juror.status === 'draft'">{{ __('institution.competitions.jury_status_ready') }}</span>
                                    <span class="ip-badge is-pending" x-show="juror.status === 'invited'">{{ __('institution.competitions.jury_status_invited') }}</span>
                                    <button x-show="juror.status === 'invited' && juror.invitation_id" type="submit" class="ip-jury-resend" :form="`resend-invite-${juror.invitation_id}`">{{ __('institution.competitions.resend_invitation') }}</button>
                                    <button type="button" class="ip-category-remove" @click="removeJuror(jurorIndex)">{{ __('institution.competitions.remove_juror') }}</button>
                                </article>
                            </template>
                            <div class="ip-jury-empty" x-show="jurors.length === 0" x-cloak>
                                <span class="ip-jury-empty-mark">+</span>
                                <div><strong>{{ __('institution.competitions.jury_required_title') }}</strong><p>{{ __('institution.competitions.jury_required_hint') }}</p></div>
                            </div>
                        </div>
                    </section>
                @endforeach
            </div>
        </div>

        <div class="ip-alert ip-alert-warning ip-jury-send-note">
            <x-institution.icon name="warning" />
            <div><div class="ip-alert-title">{{ __('institution.competitions.jury_send_note_title') }}</div><div class="ip-alert-text">{{ __('institution.competitions.jury_send_note') }}</div></div>
        </div>

        <div class="ip-form-actions ip-form-actions-sticky"><span class="ip-save-meta">{{ __('institution.competitions.last_saved_at', ['time' => $competition->updated_at->format('d.m.Y H:i')]) }}</span><button type="submit" name="action" value="draft" class="ia-btn ia-btn-secondary">{{ __('institution.competitions.save_draft') }}</button><button type="submit" name="action" value="next" class="ia-btn">{{ __('institution.competitions.next_step') }} →</button></div>

        <dialog class="ip-field-help-dialog ip-native-dialog" x-ref="helpDialog" @click="if ($event.target === $el) closeHelp()" @cancel.prevent="closeHelp()" :aria-label="help?.title">
            <section class="ip-field-help-panel"><div class="ip-field-help-header"><h2 x-text="help?.title"></h2><button x-ref="helpClose" type="button" class="ip-field-help-close" @click="closeHelp()" aria-label="{{ __('institution.competitions.close_help') }}">×</button></div><div class="ip-field-help-body"><p class="ip-field-help-description" x-text="help?.description"></p><div class="ip-field-help-example" x-show="help?.example"><strong>{{ __('institution.competitions.help_example') }}</strong><span x-text="help?.example"></span></div></div><div class="ip-field-help-footer"><button type="button" class="ia-btn ia-btn-secondary ip-field-help-dismiss" @click="closeHelp()">{{ __('institution.field_help.done') }}</button></div></section>
        </dialog>
    </form>

    @php
        $resendInvitations = collect($categoryJurorFormData)
            ->pluck('jurors')->flatten(1)
            ->where('status', 'invited')->pluck('invitation_id')->filter()->unique();
    @endphp
    @foreach ($resendInvitations as $invitationId)
        <form id="resend-invite-{{ $invitationId }}" method="POST" action="{{ route('institution.competitions.jury-invitations.resend', [$competition, $invitationId]) }}">@csrf</form>
    @endforeach
</x-institution.app-layout>
