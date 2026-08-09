@if ($competition->isEditable() && $competition->canSubmit())
    <div class="ip-card" style="margin-top: 1.5rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem;">
        <div>
            <div class="ip-section-title" style="margin-bottom: .2rem;">{{ __('institution.competitions.ready_to_submit_title') }}</div>
            <div class="ip-section-hint" style="margin-bottom: 0;">{{ __('institution.competitions.ready_to_submit_hint') }}</div>
        </div>
        <form method="POST" action="{{ route('institution.competitions.submit', $competition) }}">
            @csrf
            <button type="submit" class="ia-btn">{{ __('institution.competitions.submit_for_approval') }}</button>
        </form>
    </div>
@endif
