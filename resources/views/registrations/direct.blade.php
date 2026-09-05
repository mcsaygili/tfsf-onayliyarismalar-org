<x-dynamic-component :component="$panel.'.app-layout'" :title="__('registration.exception_direct')">
    <div class="registration-review registration-exception">
        <a href="{{ route($panel.'.registrations.index') }}">{{ __('registration.heading') }}</a>
        <h1>{{ __('registration.exception_direct') }}</h1>
        <h2>{{ $competition->name }}</h2>
        <p>{{ __('registration.exception_hint') }}</p>
        @if($errors->any())<div class="ip-alert" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        <form method="POST" action="{{ route($panel.'.registrations.direct.lookup', $competition) }}" class="ip-card">@csrf
            <div class="ia-field"><label for="email">{{ __('registration.exception_email') }}</label><input class="ia-input" id="email" name="email" type="email" required maxlength="255" value="{{ old('email', $member?->email) }}" autocomplete="off"></div>
            <button class="ia-btn ia-btn-secondary">{{ __('registration.exception_lookup') }}</button>
        </form>
        @if($member)
            <section class="ip-card"><h2>{{ $member->first_name }} {{ $member->last_name }}</h2><p>{{ $member->email }}</p>
                <p>{{ $registration ? __('registration.number').': '.$registration->number.' · '.__('registration.'.$registration->status) : __('registration.exception_no_registration') }}</p>
                <p>{{ __('registration.exception_documents', ['min' => $registration?->document_min ?? $competition->registration_document_min, 'count' => $registration?->documents->count() ?? 0]) }}</p>
                @foreach($registration?->documents ?? [] as $document)<p>{{ __('registration.document', ['slot' => $document->slot, 'version' => $document->version]) }} · {{ __('registration.scan_'.$document->scanDisplayStatus()) }}</p>@endforeach
            </section>
            @if($registration?->status !== 'approved')
                <form method="POST" action="{{ route($panel.'.registrations.direct.store', $competition) }}" class="ip-card">@csrf
                    <input type="hidden" name="user_id" value="{{ $member->id }}"><input type="hidden" name="version" value="{{ $registration?->version ?? 0 }}"><input type="hidden" name="grant_version" value="{{ $grant->version }}">
                    <input type="hidden" name="waive_documents" value="0">
                    <label class="registration-exception-check"><input type="checkbox" name="waive_documents" value="1" @checked(old('waive_documents'))><span>{{ __('registration.exception_waive') }}</span></label>
                    <p id="waiver-hint">{{ __('registration.exception_waive_hint') }}</p>
                    <div class="ia-field"><label for="reason">{{ __('registration.exception_reason') }}</label><textarea class="ia-input" id="reason" name="reason" rows="4" required minlength="10" maxlength="2000" aria-describedby="reason-hint">{{ old('reason') }}</textarea><p id="reason-hint">{{ __('registration.exception_reason_hint') }}</p></div>
                    <button class="ia-btn">{{ __('registration.exception_approve') }}</button>
                </form>
            @else<p>{{ __('registration.exception_already_approved') }}</p>@endif
        @endif
    </div>
</x-dynamic-component>
