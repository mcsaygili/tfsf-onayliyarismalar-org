<x-eys.app-layout :title="__('eys.competitions.title')">
    @php $submittedResultContext = old('result_context', $resultContext); @endphp
    <div class="ip-panel-stack">
        @if($errors->any())<div class="ip-alert ip-alert-warning" role="alert"><div class="ip-alert-text">{{ $errors->first() }}</div></div>@endif
        <x-eys.breadcrumb :crumbs="[
            ['label' => __('eys.nav.dashboard'), 'url' => route('eys.dashboard')],
            ['label' => __('eys.module_names.Institution'), 'url' => route('eys.institution.dashboard')],
            ['label' => __('eys.competitions.title'), 'url' => route('eys.competitions.index')],
            ['label' => $competition->name ?: __('eys.competitions.untitled')],
        ]" />

        <div class="ip-card">
            <div class="ip-section-title">{{ $competition->name ?: __('eys.competitions.untitled') }}</div>
            <div class="ip-section-hint">
                {{ $competition->institution?->name }}
                &middot;
                <span class="ip-badge {{ $competition->status->badgeClass() }}">
                    {{ __('eys.competitions.status.'.$competition->status->value) }}
                </span>
            </div>

            @if ($competition->latest_review_message)
                <div class="ip-alert ip-alert-warning">
                    <x-eys.icon name="warning" />
                    <div>
                        <div class="ip-alert-title">{{ __('eys.competitions.latest_message_title') }}</div>
                        <div class="ip-alert-text">{{ $competition->latest_review_message }}</div>
                    </div>
                </div>
            @endif
        </div>

        @can('institution.secretariats.manage')<p><a href="{{ route('eys.competitions.secretariat', $competition) }}">{{ __('secretariat.assignment') }}</a></p>@endcan
        @can('manageRegistrationExceptions', $competition)
            <p><a href="{{ route('eys.competitions.registration-permissions', $competition) }}">{{ __('registration.exception_permissions') }}</a></p>
        @endcan

        @if($competition->status === \App\Enums\CompetitionStatus::Approved)
            <section class="ip-card">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
                    <div>
                        <div class="ip-section-title">{{ __('eys.competitions.publication_management') }}</div>
                        <div class="ip-section-hint">{{ __('eys.competitions.publication_management_hint') }}</div>
                    </div>
                    <span class="ip-badge {{ $competition->publication_state === \App\Enums\CompetitionPublicationState::Published ? 'is-approved' : 'is-waiting-requirements' }}">{{ __('eys.competitions.publication_states.'.$competition->publication_state->value) }}</span>
                </div>
                @if($errors->has('publication'))<div class="ip-alert ip-alert-warning"><x-eys.icon name="warning" /><div class="ip-alert-text">{{ $errors->first('publication') }}</div></div>@endif
                @unless($competition->publication_state === \App\Enums\CompetitionPublicationState::Cancelled)
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:.85rem;margin-top:1rem;">
                        @if(in_array($competition->publication_state, [\App\Enums\CompetitionPublicationState::Suspended, \App\Enums\CompetitionPublicationState::Unpublished], true))
                            <form method="POST" action="{{ route('eys.competitions.publication.update', [$competition, 'resume']) }}" style="padding:1rem;border:1px solid var(--ia-surface-border);border-radius:.75rem;">@csrf
                                <strong style="color:var(--ia-cream);">{{ __('eys.competitions.publication_resume') }}</strong><p class="ip-section-hint">{{ __('eys.competitions.publication_resume_hint') }}</p><button class="ia-btn" type="submit">{{ __('eys.competitions.publication_resume') }}</button>
                            </form>
                        @endif
                        @if($competition->publication_state === \App\Enums\CompetitionPublicationState::Published)
                            <form method="POST" action="{{ route('eys.competitions.publication.update', [$competition, 'suspend']) }}" style="padding:1rem;border:1px solid var(--ia-surface-border);border-radius:.75rem;">@csrf
                                <strong style="color:var(--ia-cream);">{{ __('eys.competitions.publication_suspend') }}</strong><p class="ip-section-hint">{{ __('eys.competitions.publication_suspend_hint') }}</p><textarea class="ia-input" name="reason" rows="2" required minlength="10" maxlength="2000" placeholder="{{ __('eys.competitions.publication_reason') }}"></textarea><button class="ia-btn ia-btn-secondary" type="submit" style="margin-top:.65rem;">{{ __('eys.competitions.publication_suspend') }}</button>
                            </form>
                        @endif
                        @if(in_array($competition->publication_state, [\App\Enums\CompetitionPublicationState::Published, \App\Enums\CompetitionPublicationState::Suspended], true))
                            <form method="POST" action="{{ route('eys.competitions.publication.update', [$competition, 'unpublish']) }}" style="padding:1rem;border:1px solid var(--ia-surface-border);border-radius:.75rem;">@csrf
                                <strong style="color:var(--ia-cream);">{{ __('eys.competitions.publication_unpublish') }}</strong><p class="ip-section-hint">{{ __('eys.competitions.publication_unpublish_hint') }}</p><textarea class="ia-input" name="reason" rows="2" required minlength="10" maxlength="2000" placeholder="{{ __('eys.competitions.publication_reason') }}"></textarea><button class="ia-btn ia-btn-secondary" type="submit" style="margin-top:.65rem;">{{ __('eys.competitions.publication_unpublish') }}</button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('eys.competitions.publication.update', [$competition, 'cancel']) }}" style="padding:1rem;border:1px solid rgba(224,133,122,.3);border-radius:.75rem;">@csrf
                            <strong style="color:#e0857a;">{{ __('eys.competitions.publication_cancel') }}</strong><p class="ip-section-hint">{{ __('eys.competitions.publication_cancel_hint') }}</p><textarea class="ia-input" name="reason" rows="2" required minlength="10" maxlength="2000" placeholder="{{ __('eys.competitions.publication_reason') }}"></textarea><button class="ia-btn ia-btn-secondary" type="submit" style="margin-top:.65rem;">{{ __('eys.competitions.publication_cancel') }}</button>
                        </form>
                    </div>
                @endunless
            </section>
        @endif

        @foreach ($steps as $number => $stepDef)
            @if (! in_array($number, [9, 11], true) && $stepDef->isApplicable($competition) && $stepDef->isImplemented())
                <section class="ip-card" id="competition-step-{{ $number }}">
                    <div class="ip-section-title">{{ $stepDef->label() }}</div>
                    @foreach ($stepDef->data($competition) as $field => $fieldValue)
                        @if ($field === 'categories' && $number === 6)
                            @foreach ($competition->categories as $category)
                                <div class="ia-field" style="padding: 1rem 0; border-bottom: 1px solid var(--ia-surface-border);">
                                    <strong style="color: var(--ia-cream);">{{ $category->getTranslation('tr', false)?->name ?: '—' }}</strong>
                                    @if ($category->getTranslation('en', false)?->name)<span style="color: var(--ia-muted);"> / {{ $category->getTranslation('en', false)?->name }}</span>@endif
                                    <div style="margin-top: .65rem; color: var(--ia-muted); font-size: .82rem; line-height: 1.7;">
                                        <div><b>{{ __('eys.competitions.fields.genders') }}:</b> {{ $category->genders->pluck('name')->join(', ') }}</div>
                                        <div><b>{{ __('eys.competitions.fields.birth_date') }}:</b> {{ $category->ageEligibilityRule?->name ?: '—' }}</div>
                                        <div><b>{{ __('eys.competitions.fields.member_groups') }}:</b> {{ $category->memberGroups->pluck('name')->join(', ') }}</div>
                                        <div><b>{{ __('eys.competitions.fields.capture_devices') }}:</b> {{ $category->captureDevices->pluck('name')->join(', ') }}</div>
                                        <div><b>{{ __('eys.competitions.fields.processing_methods') }}:</b> {{ $category->processingMethods->pluck('name')->join(', ') }}</div>
                                    </div>
                                </div>
                            @endforeach
                        @elseif ($field === 'categories' && $number === 7)
                            @foreach ($competition->categories as $category)
                                <div class="ia-field" style="padding: 1rem 0; border-bottom: 1px solid var(--ia-surface-border);">
                                    <strong style="color: var(--ia-cream);">{{ $category->getTranslation('tr', false)?->name ?: '—' }}</strong>
                                    @if ($category->getTranslation('en', false)?->name)<span style="color: var(--ia-muted);"> / {{ $category->getTranslation('en', false)?->name }}</span>@endif

                                    @forelse ($category->awards as $award)
                                        <div style="margin-top: .75rem; padding: .75rem; border: 1px solid var(--ia-surface-border); border-radius: .65rem; color: var(--ia-muted); font-size: .82rem; line-height: 1.7;">
                                            <div style="color: var(--ia-cream); font-weight: 700;">
                                                {{ $award->awardReference?->name ?: '—' }}
                                                <span style="color: var(--ia-muted); font-weight: 400;">&middot; {{ __('eys.competitions.fields.award_quantity') }}: {{ $award->quantity }}</span>
                                            </div>
                                            @foreach (['tr', 'en'] as $locale)
                                                @php
                                                    $translation = $award->getTranslation($locale, false);
                                                @endphp
                                                @if ($translation?->special_award_text || $translation?->material_award)
                                                    <div style="margin-top: .35rem;">
                                                        <b>{{ strtoupper($locale) }}</b>
                                                        @if ($translation?->special_award_text)
                                                            &middot; {{ __('eys.competitions.fields.special_award_text') }}: {{ $translation->special_award_text }}
                                                        @endif
                                                        @if ($translation?->material_award)
                                                            &middot; {{ __('eys.competitions.fields.material_award') }}: {{ $translation->material_award }}
                                                        @endif
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    @empty
                                        <div style="margin-top: .65rem; color: var(--ia-muted);">{{ __('eys.competitions.no_category_awards') }}</div>
                                    @endforelse
                                </div>
                            @endforeach
                        @elseif ($field === 'categories' && $number === 8)
                            @foreach ($competition->categories as $category)
                                <div class="ia-field" style="padding: 1rem 0; border-bottom: 1px solid var(--ia-surface-border);">
                                    <strong style="color: var(--ia-cream);">{{ $category->getTranslation('tr', false)?->name ?: '—' }}</strong>
                                    @if ($category->getTranslation('en', false)?->name)<span style="color: var(--ia-muted);"> / {{ $category->getTranslation('en', false)?->name }}</span>@endif

                                    @forelse ($category->jurorAssignments as $assignment)
                                        @php
                                            $reviewJuror = $assignment->juror;
                                            $reviewInvitation = $assignment->invitation;
                                            $reviewInvitationStatus = $reviewInvitation?->status();
                                        @endphp
                                        <div style="display: flex; align-items: center; flex-wrap: wrap; gap: .55rem; margin-top: .75rem; padding: .75rem; border: 1px solid var(--ia-surface-border); border-radius: .65rem; color: var(--ia-muted); font-size: .82rem;">
                                            <strong style="color: var(--ia-cream);">
                                                {{ $reviewJuror ? trim($reviewJuror->first_name.' '.$reviewJuror->last_name) : trim(($reviewInvitation?->first_name ?? '').' '.($reviewInvitation?->last_name ?? '')) }}
                                            </strong>
                                            <span>&middot; {{ $reviewJuror?->email ?: $reviewInvitation?->email }}</span>
                                            <span class="ip-badge {{ $reviewJuror ? 'is-active' : $reviewInvitationStatus?->badgeClass() }}">
                                                {{ $reviewJuror ? __('eys.competitions.jury_registered') : __('eys.competitions.jury_invitation_status.'.$reviewInvitationStatus?->value) }}
                                            </span>
                                            @if ($reviewInvitation?->sent_at)
                                                <span>&middot; {{ __('eys.competitions.jury_last_sent', ['date' => $reviewInvitation->sent_at->format('d.m.Y H:i'), 'count' => $reviewInvitation->send_count]) }}</span>
                                            @endif
                                            @if ($reviewInvitation?->expires_at)
                                                <span>&middot; {{ __('eys.competitions.jury_expires_at', ['date' => $reviewInvitation->expires_at->format('d.m.Y H:i')]) }}</span>
                                            @endif
                                        </div>
                                    @empty
                                        <div style="margin-top: .65rem; color: var(--ia-muted);">{{ __('eys.competitions.no_category_jurors') }}</div>
                                    @endforelse

                                    <div style="margin-top: 1rem; color: var(--ia-muted-dim); font-size: .72rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase;">{{ __('eys.competitions.fields.evaluation_criteria') }}</div>
                                    @forelse ($category->evaluationCriteria as $criterionAssignment)
                                        <div style="display: grid; grid-template-columns: minmax(0, 1fr) auto auto; align-items: center; gap: 1rem; margin-top: .55rem; padding: .75rem; border: 1px solid var(--ia-surface-border); border-radius: .65rem; color: var(--ia-muted); font-size: .82rem;">
                                            <span><strong style="color: var(--ia-cream);">{{ $criterionAssignment->criterion?->name ?: '—' }}</strong>@if ($criterionAssignment->criterion?->description)<small style="display:block; margin-top:.2rem; color:var(--ia-muted-dim);">{{ $criterionAssignment->criterion->description }}</small>@endif</span>
                                            <span>{{ __('eys.competitions.fields.score_range') }}: <strong style="color: var(--ia-cream);">{{ $criterionAssignment->min_score }}–{{ $criterionAssignment->max_score }}</strong></span>
                                            <span>{{ __('eys.competitions.fields.relative_weight') }}: <strong style="color: var(--ia-cream);">{{ rtrim(rtrim($criterionAssignment->weight, '0'), '.') }}</strong></span>
                                        </div>
                                    @empty
                                        <div style="margin-top: .55rem; color: var(--ia-muted);">{{ __('eys.competitions.no_category_evaluation_criteria') }}</div>
                                    @endforelse
                                </div>
                            @endforeach
                        @elseif ($field === 'regions')
                            <div class="ia-field">
                                <x-eys.label :value="__('eys.competitions.fields.capture_regions')" />
                                @php
                                    $reviewRegions = $competition->captureRegions->isNotEmpty()
                                        ? $competition->captureRegions
                                        : collect([$competition]);
                                @endphp
                                @foreach ($reviewRegions as $region)
                                    <div style="margin-top: .5rem; color: var(--ia-cream);">
                                        {{ $region->country?->official_name ?: '—' }} / {{ $region->city?->official_name ?: '—' }}
                                    </div>
                                @endforeach
                            </div>
                        @elseif ($field === 'regulation_inputs')
                            <div class="ia-field">
                                <x-eys.label :value="__('eys.competitions.fields.regulation_inputs')" />
                                @forelse ($competition->regulationInputs as $input)
                                    <div style="margin-top: .5rem; color: var(--ia-cream); white-space: pre-line;">{{ strtoupper($input->locale) }} — {{ $input->content ?: '—' }}</div>
                                @empty
                                    <div style="color: var(--ia-cream);">—</div>
                                @endforelse
                            </div>
                        @elseif (is_array($fieldValue))
                            <div class="ia-field">
                                <x-eys.label :value="config('locales.supported.'.$field, strtoupper($field))" />
                                @foreach ($fieldValue as $translatedField => $translatedValue)
                                    <div style="margin-top: .75rem;">
                                        <div style="font-size: .76rem; color: var(--ia-muted-dim); margin-bottom: .25rem;">{{ __('eys.competitions.fields.'.$translatedField) }}</div>
                                        <div style="color: var(--ia-cream); white-space: pre-line;">{{ $translatedValue ?: '—' }}</div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            @php
                                $translationKey = 'eys.competitions.field_values.'.$field.'.'.$fieldValue;
                                $displayValue = match (true) {
                                    $field === 'competition_type' => $competition->competitionType?->name,
                                    $field === 'country' => $competition->country?->official_name,
                                    $field === 'city' => $competition->city?->official_name,
                                    $field === 'participant_approval_process' => $competition->participantApprovalProcess?->name,
                                    $fieldValue && trans()->has($translationKey) => __($translationKey),
                                    default => $fieldValue,
                                };
                            @endphp
                            <div class="ia-field">
                                <x-eys.label :value="__('eys.competitions.fields.'.$field)" />
                                <div style="color: var(--ia-cream); white-space: pre-line;">{{ $displayValue ?: '—' }}</div>
                            </div>
                        @endif
                    @endforeach
                </section>
            @endif
        @endforeach

        <div class="ip-card">
            <div class="ip-section-title">{{ __('eys.competitions.regulation_title') }}</div>
            <div class="ip-section-hint">
                {{ $regulationSnapshot
                    ? __('eys.competitions.regulation_snapshot', ['version' => $regulationSnapshot->version, 'date' => $regulationSnapshot->compiled_at->format('d.m.Y H:i')])
                    : __('eys.competitions.regulation_live') }}
            </div>
            @foreach ($compiledRegulation as $locale => $sections)
                <section style="margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid var(--ia-surface-border);">
                    <strong style="color: var(--ia-copper-bright);">{{ strtoupper($locale) }}</strong>
                    @foreach ($sections as $sectionIndex => $section)
                        <h3 style="margin: 1.1rem 0 .6rem; color: var(--ia-cream); font-size: .9rem;">{{ $sectionIndex + 1 }}. {{ $section['title'] }}</h3>
                        @foreach ($section['items'] as $itemIndex => $item)
                            <p style="display: grid; grid-template-columns: 2.7rem 1fr; gap: .6rem; margin: .5rem 0; color: var(--ia-muted); font-size: .8rem; line-height: 1.65;"><span style="color: var(--ia-muted-dim);">{{ $sectionIndex + 1 }}.{{ $itemIndex + 1 }}</span><span>{{ $item['content'] }}</span></p>
                        @endforeach
                    @endforeach
                </section>
            @endforeach
        </div>

        <div class="ip-card">
            <div class="ip-section-title">{{ __('eys.competitions.representative_title') }}</div>
            <div class="ip-section-hint">{{ __('eys.competitions.representative_hint') }}</div>
            <form method="POST" action="{{ route('eys.competitions.assign-representative', $competition) }}" style="display:flex;gap:.75rem;align-items:end;margin-top:1rem;">@csrf @method('PATCH')
                <div class="ia-field" style="flex:1;margin:0;"><x-eys.label for="representative_id" :value="__('eys.competitions.representative')" /><select id="representative_id" name="representative_id" class="ia-input"><option value="">{{ __('eys.competitions.representative_unassigned') }}</option>@foreach($representatives as $representative)<option value="{{ $representative->id }}" @selected($competition->representative_id === $representative->id)>{{ trim($representative->first_name.' '.$representative->last_name) ?: $representative->email }}</option>@endforeach</select></div>
                <button class="ia-btn">{{ __('eys.common.save') }}</button>
            </form>
            @if($competition->monitoringReports->isNotEmpty())
                <div class="ip-table-wrap" style="margin-top:1.25rem;"><table class="ip-table"><thead><tr><th>Tarih</th><th>Temsilci</th><th>Durum</th><th>Konu</th><th>Not</th></tr></thead><tbody>
                    @foreach($competition->monitoringReports as $report)<tr><td>{{ $report->observed_at->format('d.m.Y H:i') }}</td><td>{{ trim(($report->representative?->first_name ?? '').' '.($report->representative?->last_name ?? '')) }}</td><td>{{ $report->status }}</td><td class="ip-cell-name">{{ $report->subject }}</td><td>{{ $report->note }}</td></tr>@endforeach
                </tbody></table></div>
            @endif
        </div>

        @php
            $individualRound = $competition->evaluationRounds->first(fn ($round) => $round->method?->value === 'individual') ?? $competition->evaluationRounds->first();
            $finalRound = $competition->evaluationRounds->firstWhere('is_final', true);
            $evaluationRound = $finalRound ?? $individualRound;
            $resultAwardAssignments = $evaluationRound?->results
                ->flatMap->awards
                ->keyBy(fn ($assignment) => $assignment->competition_category_award_id.'.'.$assignment->slot_number)
                ?? collect();
        @endphp
        <div class="ip-card">
            <div class="ip-toolbar"><div><div class="ip-toolbar-title">{{ __('eys.competitions.results_title') }}</div><div class="ip-toolbar-hint">{{ __('eys.competitions.results_hint') }}</div></div><div style="display:flex;gap:.5rem;"><a class="ia-btn ia-btn-secondary" href="{{ route('eys.competitions.reports.entries', $competition) }}">Katılım CSV</a>@if($evaluationRound)<a class="ia-btn ia-btn-secondary" href="{{ route('eys.competitions.reports.results', $competition) }}">Sonuç CSV</a>@endif</div></div>
            @if($evaluationRound)
                @unless($competition->results_published_at || $resultFreshness->get($evaluationRound->id))<div class="ip-alert ip-alert-warning" role="status">{{ __($evaluationRound->results_state_hash ? 'result_selection.recalculate' : 'result_selection.prepare') }}</div>@endunless
                @if(!$competition->results_published_at && $competition->categories->flatMap->awards->sum('quantity') > 0 && !$awardFreshness->get($evaluationRound->id))<div class="ip-alert ip-alert-warning" role="status">{{ __($evaluationRound->awards_context_hash ? 'result_selection.review_awards' : 'result_selection.assign') }}</div>@endif
                <div style="display:flex;gap:1.25rem;flex-wrap:wrap;margin:1rem 0;"><span>{{ __('eys.competitions.active_result_round') }}: <strong>{{ $evaluationRound->round_number }} · {{ $evaluationRound->name }}</strong></span><span>{{ __('eys.competitions.completed_jury_evaluations') }}: <strong>{{ $individualRound?->evaluationSubmissions->count() ?? 0 }}</strong></span><span>{{ __('eys.competitions.calculated_results') }}: <strong>{{ $evaluationRound->results->count() }}</strong></span></div>

                @if(!$finalRound && $individualRound?->results->isNotEmpty() && !$competition->results_published_at)
                    <form method="POST" action="{{ route('eys.competitions.create-final-round', $competition) }}" style="margin:1rem 0 1.5rem;padding:1rem;border:1px solid rgba(201,168,76,.3);border-radius:.75rem;">@csrf
                        <input type="hidden" name="result_context" value="{{ is_string($submittedResultContext) ? $submittedResultContext : '' }}">
                        <div class="ip-section-title" style="font-size:1rem;">{{ __('eys.competitions.create_final_round') }}</div>
                        <div class="ip-section-hint">{{ __('eys.competitions.create_final_round_hint') }}</div>
                        @if($resultAwardAssignments->isNotEmpty())<p class="ip-section-hint">{{ __('result_selection.final_resets_awards') }}</p>@endif
                        <div class="ip-finalist-grid">
                            @foreach($individualRound->results->sortBy(fn ($result) => sprintf('%s-%05d', $result->photo->submission->competition_category_id, $result->rank)) as $result)
                                <div class="ip-finalist-work">
                                    <x-eys.work-preview :photo="$result->photo" :competition="$competition" />
                                    <label class="ip-finalist-choice"><input type="checkbox" name="photo_result_ids[]" value="{{ $result->id }}" @checked(in_array($result->id, (array) old('photo_result_ids', []), true))><span><strong>{{ __('result_selection.work') }} {{ $result->photo->workCode() }}</strong><span>#{{ $result->rank }} · {{ $result->photo->submission->category->name }}</span><small>{{ __('eys.competitions.result_score_option', ['score' => $result->average_score]) }}</small></span></label>
                                </div>
                            @endforeach
                        </div>
                        @error('photo_result_ids')<small style="color:#e0857a;">{{ $message }}</small>@enderror
                        <div style="display:flex;justify-content:flex-end;margin-top:.85rem;"><button class="ia-btn">{{ __('eys.competitions.start_final_round') }}</button></div>
                    </form>
                @endif

                @if($finalRound && !$competition->results_published_at)
                    @php $jurySession = $finalRound->jurySession; @endphp
                    @if($jurySession)
                        <form method="POST" action="{{ route('eys.competitions.jury-session.update', $competition) }}" style="margin:1rem 0 1.5rem;padding:1rem;border:1px solid var(--ia-surface-border);border-radius:.75rem;">@csrf @method('PUT')
                            <input type="hidden" name="session_version" value="{{ old('session_version', $jurySession->version) }}">
                            <fieldset @disabled($jurySession->status === 'closed') style="border:0;padding:0;min-width:0;">
                            <div class="ip-toolbar"><div><div class="ip-toolbar-title">Final kurul oturumu</div><div class="ip-toolbar-hint">Yüz yüze ortak değerlendirme için zaman, yeter sayı, katılım ve tutanak kaydı.</div></div><span class="ip-badge {{ $jurySession->status === 'closed' ? 'is-approved' : ($jurySession->status === 'open' ? 'is-under-review' : 'is-draft') }}">{{ ['planned'=>'Planlandı','open'=>'Açık','closed'=>'Kapalı'][$jurySession->status] }}</span></div>
                            <div class="ip-grid-3">
                                <div class="ia-field"><x-eys.label for="session_scheduled_at" value="Toplantı zamanı" /><input class="ia-input" id="session_scheduled_at" type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at', $jurySession->scheduled_at?->format('Y-m-d\TH:i')) }}"></div>
                                <div class="ia-field"><x-eys.label for="session_location" value="Toplantı yeri" /><input class="ia-input" id="session_location" name="location" maxlength="255" value="{{ old('location', $jurySession->location) }}"></div>
                                <div class="ia-field"><x-eys.label for="session_quorum" value="Yeter sayı" /><input class="ia-input" id="session_quorum" type="number" min="1" max="30" name="quorum" value="{{ old('quorum', $jurySession->quorum) }}" required></div>
                            </div>
                            <div class="ip-table-wrap"><table class="ip-table"><thead><tr><th>Jüri</th><th>Katılım</th><th>Çıkar çatışması</th></tr></thead><tbody>
                                @foreach($jurySession->attendances as $attendance)<tr><td class="ip-cell-name">{{ trim($attendance->juror->first_name.' '.$attendance->juror->last_name) }}</td><td>@if($jurySession->status === 'closed'){{ ['invited'=>'Davetli','present'=>'Katıldı','absent'=>'Katılmadı'][$attendance->attendance_status] }}@else<select class="ia-input" style="min-width:100px;" name="attendances[{{ $attendance->id }}]">@foreach(['invited'=>'Davetli','present'=>'Katıldı','absent'=>'Katılmadı'] as $value=>$label)<option value="{{ $value }}" @selected(old('attendances.'.$attendance->id, $attendance->attendance_status) === $value)>{{ $label }}</option>@endforeach</select>@endif</td><td>{{ $attendance->declared_at ? ($attendance->conflict_declared ? 'Beyan edildi — '.$attendance->conflict_note : 'Bulunmuyor') : 'Beyan bekleniyor' }}</td></tr>@endforeach
                            </tbody></table></div>
                            <div class="ia-field" style="margin-top:1rem;"><x-eys.label for="session_minutes" value="Kurul tutanağı" /><textarea class="ia-input" id="session_minutes" name="minutes" maxlength="10000">{{ old('minutes', $jurySession->minutes) }}</textarea></div>
                            @error('session')<div class="ia-error">{{ $message }}</div>@enderror
                            <div style="display:flex;justify-content:flex-end;gap:.6rem;">@unless($jurySession->status === 'closed')<button class="ia-btn ia-btn-secondary" name="action" value="save">Planı kaydet</button>@endunless
                            @if($jurySession->status === 'planned')<button class="ia-btn" name="action" value="open">Oturumu aç</button>@elseif($jurySession->status === 'open')<button class="ia-btn" name="action" value="close">Oturumu kapat</button>@endif</div>
                         </fieldset>
</form>
                    @endif
                    <form method="POST" action="{{ route('eys.competitions.save-final-round', $competition) }}" style="margin:1rem 0 1.5rem;">@csrf @method('PUT')
                        <input type="hidden" name="session_version" value="{{ old('session_version', $jurySession?->version) }}">
                        <fieldset @disabled($jurySession?->status !== 'open') style="border:0;padding:0;min-width:0;">
                        <div class="ip-section-title" style="font-size:1rem;">{{ __('eys.competitions.committee_evaluation') }}</div>
                        <div class="ip-section-hint">{{ __('eys.competitions.committee_evaluation_hint') }}</div>
                        <div class="ip-table-wrap" style="margin-top:.85rem;"><table class="ip-table"><thead><tr><th>{{ __('eys.competitions.result_category') }}</th><th>{{ __('eys.competitions.committee_decision') }}</th><th>{{ __('eys.competitions.committee_score') }}</th><th>{{ __('eys.competitions.result_rank') }}</th><th>{{ __('eys.competitions.committee_note') }}</th></tr></thead><tbody>
                            @foreach($finalRound->committeeDecisions as $decision)
                                <tr><td><x-eys.work-preview :photo="$decision->photo" :competition="$competition" />{{ $decision->photo->submission->category->name }}</td><td><select class="ia-input" name="decisions[{{ $decision->id }}][decision]">@foreach(['finalist','selected','not_selected'] as $status)<option value="{{ $status }}" @selected(old('decisions.'.$decision->id.'.decision', $decision->decision->value) === $status)>{{ __('eys.competitions.committee_decisions.'.$status) }}</option>@endforeach</select></td><td><input class="ia-input" type="number" min="3" max="9" name="decisions[{{ $decision->id }}][score]" value="{{ old('decisions.'.$decision->id.'.score', $decision->score) }}"></td><td><input class="ia-input" type="number" min="1" name="decisions[{{ $decision->id }}][rank]" value="{{ old('decisions.'.$decision->id.'.rank', $decision->rank) }}">@error('decisions.'.$decision->id.'.rank')<small style="color:#e0857a;">{{ $message }}</small>@enderror</td><td><input class="ia-input" type="text" maxlength="2000" name="decisions[{{ $decision->id }}][note]" value="{{ old('decisions.'.$decision->id.'.note', $decision->note) }}"></td></tr>
                            @endforeach
                        </tbody></table></div>
                        <div style="display:flex;justify-content:flex-end;margin-top:.85rem;">@if($jurySession?->status === 'open')<button class="ia-btn">{{ __('eys.competitions.save_committee_evaluation') }}</button>@endif</div>
                     </fieldset>
</form>
                @endif
                @if($evaluationRound->results->isNotEmpty())
                    <div class="ip-table-wrap"><table class="ip-table"><thead><tr><th>{{ __('eys.competitions.result_rank') }}</th><th>{{ __('eys.competitions.result_category') }}</th><th>{{ __('eys.competitions.result_total') }}</th><th>{{ __('eys.competitions.result_average') }}</th><th>{{ __('eys.competitions.result_score_count') }}</th><th>{{ __('eys.competitions.result_awards') }}</th></tr></thead><tbody>
                    @foreach($evaluationRound->results->sortBy(fn ($result) => sprintf('%s-%05d', $result->photo->submission->competition_category_id, $result->rank)) as $result)
                        <tr><td>{{ $result->rank }}</td><td><x-eys.work-preview :photo="$result->photo" :competition="$competition" />{{ $result->photo->submission->category->name }}</td><td>{{ $result->total_score }}</td><td>{{ $result->average_score }}</td><td>{{ $result->score_count }}</td><td>@forelse($result->awards as $assignment)<span class="ip-badge is-approved">{{ $assignment->categoryAward->awardReference?->name ?: $assignment->categoryAward->special_award_text }}</span>@empty—@endforelse</td></tr>
                    @endforeach
                    </tbody></table></div>

                    @if($competition->categories->flatMap->awards->sum('quantity') > 0)
                        <form method="POST" action="{{ route('eys.competitions.save-result-awards', $competition) }}" style="margin-top:1.5rem;">@csrf
                        <input type="hidden" name="result_context" value="{{ is_string($submittedResultContext) ? $submittedResultContext : '' }}"> @method('PUT')
                            <div class="ip-section-title" style="font-size:1rem;">{{ __('eys.competitions.result_award_assignment_title') }}</div>
                            <div class="ip-section-hint">{{ __('eys.competitions.result_award_assignment_hint') }}</div>
                            <div style="display:grid;gap:1rem;margin-top:1rem;">
                                @foreach($competition->categories as $category)
                                    @if($category->awards->isNotEmpty())
                                        @php
                                            $categoryResults = $evaluationRound->results->filter(fn ($result) => $result->photo->submission->competition_category_id === $category->id)->sortBy('rank');
                                            $previewOptions = $categoryResults->mapWithKeys(fn ($result) => [$result->id => ['code' => $result->photo->workCode(), 'url' => $result->photo->jury_sanitized_at && $result->photo->jury_path && !$result->photo->withdrawn_at && $result->photo->submission->status->value === 'approved' ? route('eys.competitions.results.photos.show', [$competition, $result->photo]) : null]]);
                                        @endphp
                                        <section style="padding:1rem;border:1px solid var(--ia-surface-border);border-radius:.75rem;">
                                            <strong style="color:var(--ia-cream);">{{ $category->name }}</strong>
                                            <div class="ip-award-grid">
                                                @foreach($category->awards as $categoryAward)
                                                    @for($slot = 1; $slot <= $categoryAward->quantity; $slot++)
                                                        @php
                                                            $assignmentKey = $categoryAward->id.'.'.$slot;
                                                            $selectedResultId = old('award_assignments.'.$categoryAward->id.'.'.$slot, $resultAwardAssignments->get($assignmentKey)?->competition_photo_result_id);
                                                            $awardName = $categoryAward->awardReference?->name ?: $categoryAward->special_award_text;
                                                        @endphp
                                                        <div class="ip-award-choice" x-data="{ selected: @js(is_string($selectedResultId) ? $selectedResultId : ''), works: @js($previewOptions), failed: false }" x-effect="selected; failed = false">
                                                        <label class="ia-field" style="margin:0;">
                                                            <span>{{ $awardName }} @if($categoryAward->quantity > 1)<small>({{ $slot }}/{{ $categoryAward->quantity }})</small>@endif</span>
                                                            <select class="ia-input" x-model="selected" name="award_assignments[{{ $categoryAward->id }}][{{ $slot }}]" @disabled($competition->results_published_at)>
                                                                <option value="">{{ __('eys.competitions.result_award_unassigned') }}</option>
                                                                @foreach($categoryResults as $result)
                                                                    <option value="{{ $result->id }}" @selected($selectedResultId === $result->id)>{{ $result->photo->workCode() }}@if($result->photo->submission->category->photos_grouped) · {{ $result->photo->submission->seriesCode() }}@endif · #{{ $result->rank }} · {{ __('eys.competitions.result_score_option', ['score' => $result->average_score]) }}</option>
                                                                @endforeach
                                                            </select>
                                                            @error('award_assignments.'.$categoryAward->id.'.'.$slot)<small style="color:#e0857a;">{{ $message }}</small>@enderror
                                                        </label>
                                                        <template x-if="works[selected]">
                                                            <div class="ip-work-preview">
                                                                <strong class="ip-work-code" x-text="@js(__('result_selection.work')).concat(' ', works[selected].code)"></strong>
                                                                <template x-if="works[selected].url && !failed">
                                                                    <a class="ip-work-image" :href="works[selected].url" target="_blank" rel="noopener">
                                                                        <img :src="works[selected].url" :alt="@js(__('result_selection.work')).concat(' ', works[selected].code)" loading="lazy" decoding="async" x-on:error="failed = true">
                                                                        <span>{{ __('result_selection.preview') }}</span>
                                                                    </a>
                                                                </template>
                                                                <span class="ip-work-unavailable" x-show="!works[selected].url || failed">{{ __('result_selection.unavailable') }}</span>
                                                            </div>
                                                        </template>
                                                        <p class="ip-section-hint" x-show="!works[selected]">{{ __('result_selection.choose') }}</p>
                                                        <noscript><p class="ip-section-hint">{{ __('result_selection.work') }}: {{ $categoryResults->firstWhere('id', $selectedResultId)?->photo->workCode() ?? '—' }}</p></noscript>
                                                        </div>
                                                    @endfor
                                                @endforeach
                                            </div>
                                        </section>
                                    @endif
                                @endforeach
                            </div>
                            @unless($competition->results_published_at)<div style="display:flex;justify-content:flex-end;margin-top:1rem;"><button class="ia-btn ia-btn-secondary">{{ __('eys.competitions.save_result_awards') }}</button></div>@endunless
                        </form>
                    @endif
                @endif
                <div style="display:flex;gap:.75rem;justify-content:flex-end;align-items:flex-start;flex-wrap:wrap;margin-top:1rem;">
                    @if($evaluationRound->results->isNotEmpty())<a class="ia-btn ia-btn-secondary" href="{{ route('eys.competitions.preview-results', $competition) }}">{{ __('eys.competitions.preview_results') }}</a>@endif
                    <form method="POST" action="{{ route('eys.competitions.aggregate-results', $competition) }}">@csrf<button class="ia-btn ia-btn-secondary" @disabled($competition->results_published_at)>{{ __('eys.competitions.calculate_results') }}</button></form>
                    @if($competition->results_published_at)
                        <form method="POST" action="{{ route('eys.competitions.unpublish-results', $competition) }}" style="display:flex;gap:.55rem;align-items:flex-start;flex-wrap:wrap;">@csrf<input class="ia-input" name="reason" required minlength="10" maxlength="2000" placeholder="{{ __('eys.competitions.result_correction_reason') }}" style="min-width:270px;"><button class="ia-btn ia-btn-secondary">{{ __('eys.competitions.unpublish_results') }}</button></form>
                    @else
                        <form method="POST" action="{{ route('eys.competitions.publish-results', $competition) }}" style="display:flex;gap:.55rem;align-items:flex-start;flex-wrap:wrap;">@csrf
                        <input type="hidden" name="result_context" value="{{ is_string($submittedResultContext) ? $submittedResultContext : '' }}"><input class="ia-input" type="datetime-local" name="publish_at" aria-label="Planlı yayın zamanı"><input class="ia-input" name="publication_note" maxlength="2000" placeholder="Yayın notu (opsiyonel)" style="min-width:270px;"><button class="ia-btn">{{ __('eys.competitions.publish_results') }}</button></form>
                    @endif
                </div>
            @else<p style="margin-top:1rem;">{{ __('eys.competitions.no_evaluation_round') }}</p>@endif
        </div>

        @if($competition->resultPublications->isNotEmpty())
            <div class="ip-card">
                <div class="ip-section-title">Sonuç yayın geçmişi</div><div class="ip-section-hint">Her yayın ayrı ve değişmez bir sonuç snapshot’ı olarak saklanır.</div>
                <div class="ip-table-wrap"><table class="ip-table"><thead><tr><th>Versiyon</th><th>Yayın</th><th>Yayımlayan</th><th>Yayın notu</th><th>Düzeltme</th></tr></thead><tbody>
                    @foreach($competition->resultPublications as $publication)<tr><td class="ip-cell-name"><a href="{{ route('eys.competitions.preview-results', [$competition, 'version' => $publication->version]) }}">v{{ $publication->version }}</a></td><td>{{ $publication->published_at->format('d.m.Y H:i') }}@if($publication->withdrawn_at)<small style="display:block;color:var(--ia-muted-dim);">{{ $publication->withdrawn_at->format('d.m.Y H:i') }} tarihinde geri çekildi</small>@endif</td><td>{{ trim(($publication->publisher?->first_name ?? '').' '.($publication->publisher?->last_name ?? '')) ?: '—' }}</td><td>{{ $publication->publication_note ?: '—' }}</td><td>{{ $publication->correction_note ?: '—' }}</td></tr>@endforeach
                </tbody></table></div>
            </div>
        @endif

        @include('eys.competitions._review-panel')

        <div class="ip-card">
            <div class="ip-section-title">{{ __('eys.competitions.history_title') }}</div>

            @php
                $timelineItems = $competition->statusLogs->map(fn($item) => ['type' => 'status', 'model' => $item, 'at' => $item->created_at])
                    ->concat($competition->notificationDispatches->map(fn($item) => ['type' => 'notification', 'model' => $item, 'at' => $item->created_at]))
                    ->sortByDesc('at');
            @endphp
            @forelse ($timelineItems as $timelineItem)
                @php
                    $log = $timelineItem['model'];
                @endphp
                <div style="padding: .85rem 0; border-bottom: 1px solid var(--ia-surface-border);">
                    @if($timelineItem['type'] === 'status')
                    <div style="display: flex; justify-content: space-between; gap: 1rem; font-size: .85rem;">
                        <strong style="color: var(--ia-cream);">{{ __('eys.competitions.log_actions.'.$log->action) }}</strong>
                        <span style="color: var(--ia-muted-dim);">{{ $log->created_at->format('d.m.Y H:i') }}</span>
                    </div>
                    @if ($log->message)
                        <div style="font-size: .85rem; color: var(--ia-muted); margin-top: .35rem;">{{ $log->message }}</div>
                    @endif
                    @if ($log->changes)
                        <ul style="font-size: .82rem; color: var(--ia-muted); margin-top: .35rem; padding-left: 1.1rem;">
                            @foreach ($log->changes as $field => $diff)
                                @php
                                    $fieldParts = explode('.', $field, 2);
                                    $fieldLabel = count($fieldParts) === 2
                                        ? config('locales.supported.'.$fieldParts[0], strtoupper($fieldParts[0])).' / '.__('eys.competitions.fields.'.$fieldParts[1])
                                        : __('eys.competitions.fields.'.$field);
                                @endphp
                                <li><strong>{{ $fieldLabel }}:</strong>
                                    @if(is_array($diff) && array_is_list($diff) && count($diff) >= 2)
                                        <span style="color:#e0857a;">{{ $diff[0] ?: '—' }}</span> → <span style="color:#8fcf93;">{{ $diff[1] ?: '—' }}</span>
                                    @elseif(is_array($diff) && isset($diff['from'], $diff['to']))
                                        <span style="color:#e0857a;">{{ $diff['from'] ?: '—' }}</span> → <span style="color:#8fcf93;">{{ $diff['to'] ?: '—' }}</span>
                                    @else
                                        <span>{{ is_scalar($diff) ? $diff : json_encode($diff, JSON_UNESCAPED_UNICODE) }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    @else
                        <div style="display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;font-size:.85rem;">
                            <div><strong style="color:var(--ia-cream);">{{ __('eys.mail_client.timeline_notification') }}</strong><div style="color:var(--ia-muted);margin-top:.25rem;">{{ __('eys.mail_client.dispatch_types.'.$log->type) }} · {{ $log->recipient_email ?: '—' }}</div></div>
                            <div style="text-align:right;"><span class="ip-badge {{ $log->status === 'delivered' ? 'is-active' : (in_array($log->status, \App\Models\NotificationDispatch::RETRYABLE_STATUSES, true) ? 'is-inactive' : '') }}">{{ $log->status }}</span><div style="color:var(--ia-muted-dim);margin-top:.25rem;">{{ $log->created_at->format('d.m.Y H:i') }}</div></div>
                        </div>
                        @if($log->last_error)<div style="font-size:.8rem;color:#e0857a;margin-top:.35rem;">{{ $log->last_error }}</div>@endif
                    @endif
                </div>
            @empty
                <div class="ip-table-empty">{{ __('eys.competitions.no_history') }}</div>
            @endforelse
        </div>
    </div>
</x-eys.app-layout>
