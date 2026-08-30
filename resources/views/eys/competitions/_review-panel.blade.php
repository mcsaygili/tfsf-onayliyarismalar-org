@php
    $reviewStepsByNumber = $latestReview?->steps?->keyBy('step_number') ?? collect();
    $approvedCount = $reviewStepsByNumber->where('status', 'approved')->count();
    $correctionCount = $reviewStepsByNumber->where('status', 'correction_required')->count();
    $reviewTotal = $reviewStepsByNumber->count();
@endphp

<section class="ip-card ip-review-workspace" aria-labelledby="review-workspace-title">
    <div class="ip-review-heading">
        <div>
            <div class="ip-section-title" id="review-workspace-title">{{ __('eys.competitions.review_title') }}</div>
            <div class="ip-section-hint">{{ __('eys.competitions.review_hint') }}</div>
        </div>
        @if ($latestReview)
            <div class="ip-review-progress" aria-label="{{ __('eys.competitions.review_progress', ['approved' => $approvedCount, 'total' => $reviewTotal]) }}">
                <strong>{{ $approvedCount }}/{{ $reviewTotal }}</strong>
                <span>{{ __('eys.competitions.review_progress_short') }}</span>
            </div>
        @endif
    </div>

    @if ($competition->status === \App\Enums\CompetitionStatus::Submitted)
        <div class="ip-review-empty">
            <div>
                <strong>{{ __('eys.competitions.review_not_started') }}</strong>
                <p>{{ __('eys.competitions.review_not_started_hint') }}</p>
            </div>
            <form method="POST" action="{{ route('eys.competitions.start', $competition) }}">
                @csrf
                <button type="submit" class="ia-btn">{{ __('eys.competitions.action_start_review') }}</button>
            </form>
        </div>
    @elseif ($latestReview)
        <div class="ip-review-meta">
            <span>{{ __('eys.competitions.review_round', ['round' => $latestReview->round]) }}</span>
            <span>{{ trim(($latestReview->reviewer?->first_name ?? '').' '.($latestReview->reviewer?->last_name ?? '')) }}</span>
            <time datetime="{{ $latestReview->started_at?->toIso8601String() }}">{{ $latestReview->started_at?->format('d.m.Y H:i') }}</time>
        </div>

        @if ($competition->status === \App\Enums\CompetitionStatus::UnderReview)
            <form method="POST" action="{{ route('eys.competitions.save-review', $competition) }}" class="ip-review-form">
                @csrf
                @method('PATCH')

                <x-eys.input-error :messages="$errors->get('review')" />

                <div class="ip-review-list">
                    @foreach ($reviewableSteps as $number => $stepDef)
                        @php $reviewStep = $reviewStepsByNumber->get($number); @endphp
                        <article class="ip-review-row">
                            <div class="ip-review-step-copy">
                                <span class="ip-review-step-number">{{ str_pad((string) $number, 2, '0', STR_PAD_LEFT) }}</span>
                                <div>
                                    <a href="#competition-step-{{ $number }}">{{ $stepDef->label() }}</a>
                                    <p>{{ data_get(trans('institution.competitions.steps'), $number.'.hint') }}</p>
                                </div>
                            </div>
                            <div class="ip-review-decision">
                                <label for="review-status-{{ $number }}">{{ __('eys.competitions.step_decision') }}</label>
                                <select id="review-status-{{ $number }}" name="steps[{{ $number }}][status]" class="ia-input">
                                    @foreach (['pending', 'approved', 'correction_required'] as $decision)
                                        <option value="{{ $decision }}" @selected(old("steps.$number.status", $reviewStep?->status) === $decision)>
                                            {{ __('eys.competitions.review_step_status.'.$decision) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="ip-review-note">
                                <label for="review-note-{{ $number }}">{{ __('eys.competitions.step_note') }}</label>
                                <textarea id="review-note-{{ $number }}" name="steps[{{ $number }}][note]" class="ia-input" rows="2" placeholder="{{ __('eys.competitions.step_note_placeholder') }}">{{ old("steps.$number.note", $reviewStep?->note) }}</textarea>
                                <x-eys.input-error :messages="$errors->get("steps.$number.note")" />
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="ia-field ip-review-general-note">
                    <x-eys.label for="review-message" :value="__('eys.competitions.general_review_note')" />
                    <textarea id="review-message" name="message" class="ia-input" rows="2" placeholder="{{ __('eys.competitions.general_review_note_placeholder') }}">{{ old('message') }}</textarea>
                </div>

                <div class="ip-review-actions">
                    <button type="submit" class="ia-btn">{{ __('eys.competitions.action_save_review') }}</button>
                    <button type="submit" class="ia-btn" formaction="{{ route('eys.competitions.request-info', $competition) }}">
                        {{ __('eys.competitions.action_request_corrections') }}
                    </button>
                </div>
            </form>
        @else
            <div class="ip-review-list is-readonly">
                @foreach ($reviewStepsByNumber as $reviewStep)
                    <article class="ip-review-row">
                        <div class="ip-review-step-copy">
                            <span class="ip-review-step-number">{{ str_pad((string) $reviewStep->step_number, 2, '0', STR_PAD_LEFT) }}</span>
                            <div>
                                <strong>{{ $steps[$reviewStep->step_number]->label() }}</strong>
                                @if ($reviewStep->note)<p>{{ $reviewStep->note }}</p>@endif
                            </div>
                        </div>
                        <span class="ip-review-state is-{{ $reviewStep->status }}">{{ __('eys.competitions.review_step_status.'.$reviewStep->status) }}</span>
                    </article>
                @endforeach
            </div>
        @endif
    @endif

    @if (in_array($competition->status, [\App\Enums\CompetitionStatus::UnderReview, \App\Enums\CompetitionStatus::WaitingRequirements], true))
        @if ($pendingJuryAssignments->isNotEmpty())
            <div class="ip-alert ip-alert-warning ip-review-requirement" role="alert">
                <x-eys.icon name="warning" />
                <div>
                    <div class="ip-alert-title">{{ __('eys.competitions.jury_approval_blocked_title', ['count' => $pendingJuryAssignments->count()]) }}</div>
                    <div class="ip-alert-text">{{ __('eys.competitions.jury_approval_blocked') }}</div>
                    <div class="ip-alert-text" style="margin-top: .45rem;">
                        {{ $pendingJuryAssignments->map(fn ($assignment) => ($assignment->invitation?->email ?: '—').' · '.__('eys.competitions.jury_invitation_status.'.($assignment->invitation?->status()->value ?? 'draft')))->join(' · ') }}
                    </div>
                </div>
            </div>
        @endif

        <x-eys.input-error :messages="$errors->get('approval')" />

        <div class="ip-review-final-actions">
            <form method="POST" action="{{ route('eys.competitions.approve', $competition) }}">
                @csrf
                <button
                    type="button"
                    class="ia-btn"
                    @if ($pendingJuryAssignments->isEmpty() && $latestReview?->isFullyApproved()) onclick="eysConfirm(@js(__('eys.competitions.confirm_approve')), this.closest('form'))" @endif
                    @disabled($pendingJuryAssignments->isNotEmpty() || ! $latestReview?->isFullyApproved())
                >{{ __('eys.competitions.action_approve') }}</button>
            </form>
        </div>
    @endif

    @if (in_array($competition->status, [\App\Enums\CompetitionStatus::Submitted, \App\Enums\CompetitionStatus::UnderReview, \App\Enums\CompetitionStatus::WaitingRequirements], true))
        <details class="ip-review-reject">
            <summary>{{ __('eys.competitions.action_reject') }}</summary>
            <form method="POST" action="{{ route('eys.competitions.reject', $competition) }}">
                @csrf
                <x-eys.label for="rejection-message" :value="__('eys.competitions.rejection_reason')" />
                <textarea id="rejection-message" name="message" class="ia-input" rows="3" required placeholder="{{ __('eys.competitions.message_placeholder') }}"></textarea>
                <button type="submit" class="ia-btn ia-btn-secondary">{{ __('eys.competitions.action_reject') }}</button>
            </form>
        </details>
    @endif
</section>
