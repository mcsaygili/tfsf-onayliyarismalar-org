<x-dynamic-component :component="$panel.'.app-layout'" :title="__('registration.review')">
    <div class="registration-review">
    <h1>{{ $registration->competition->name }}</h1>
    <p>{{ $registration->user->first_name }} {{ $registration->user->last_name }} · {{ __('registration.number') }} {{ $registration->number }} · {{ __('registration.'.$registration->status) }}</p>
    @if($errors->any())<div class="ip-alert" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        @if($registration->approval_source === 'direct')<p>{{ __('registration.exception_by_reviewer') }}@if($registration->documents_waived) {{ __('registration.exception_waived') }}@endif</p>@endif
    @if($registration->review_note)<p>{{ $registration->review_note }}</p>@endif
    <section class="ip-card"><h2>{{ __('registration.documents') }}</h2><p>{{ __('registration.scan_hint') }}</p>
    @foreach($registration->documents->where('is_current', true)->sortBy('slot') as $document)<p>@if($document->isTrusted())<a href="{{ route($panel.'.registrations.documents.show', $document) }}">{{ __('registration.document', ['slot' => $document->slot, 'version' => $document->version]) }}</a>@else<span>{{ __('registration.document', ['slot' => $document->slot, 'version' => $document->version]) }}</span>@endif <span class="registration-document-status">{{ __('registration.scan_'.$document->scanDisplayStatus()) }}</span></p>@endforeach
    </section>
    @if(in_array($registration->status, ['pending', 'approved'], true))
    <form method="POST" action="{{ route($panel.'.registrations.decide', $registration) }}" class="ip-card">@csrf<input type="hidden" name="version" value="{{ $registration->version }}">
        <div class="ia-field"><label for="decision">{{ __('registration.decision') }}</label><select class="ia-input" id="decision" name="decision" required><option value="">{{ __('registration.choose_decision') }}</option>@foreach($registration->status === 'approved' ? ['changes_requested'] : ['approved', 'changes_requested', 'rejected'] as $decision)<option value="{{ $decision }}">{{ __('registration.action_'.$decision) }}</option>@endforeach</select></div>
        <div class="ia-field"><label for="note">{{ __('registration.note') }}</label><textarea class="ia-input" id="note" name="note" maxlength="2000">{{ old('note') }}</textarea></div>
        <button class="ia-btn">{{ __('registration.decide') }}</button>
    </form>
    @endif
    <details class="ip-card"><summary>{{ __('registration.history') }}</summary>@foreach($registration->documents->where('is_current', false) as $document)<p>@if($document->isTrusted())<a href="{{ route($panel.'.registrations.documents.show', $document) }}">{{ __('registration.document', ['slot' => $document->slot, 'version' => $document->version]) }}</a>@else<span>{{ __('registration.document', ['slot' => $document->slot, 'version' => $document->version]) }}</span>@endif <span class="registration-document-status">{{ __('registration.scan_'.$document->scanDisplayStatus()) }}</span></p>@endforeach</details>
    </div>
</x-dynamic-component>
