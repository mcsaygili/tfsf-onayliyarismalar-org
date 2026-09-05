<x-uye.app-layout :title="__('registration.heading')">
    <a class="mp-back" href="{{ route('competitions.show', $competition) }}">← {{ __('registration.back') }}</a>
    <header class="mp-detail-heading"><h1>{{ $competition->name }}</h1></header>
    @if($registration?->status === 'approved' && $registration->documents_waived)
        <p>{{ __('registration.exception_member_requirement') }}</p>
    @else
        <p>{{ __('registration.requirement', ['min' => $registration?->document_min ?? $competition->registration_document_min]) }}</p>
    @endif
    @if($errors->any())<div class="mp-callout is-error" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    @if(!$registration)
        <form method="POST" action="{{ route('competitions.registration.store', $competition) }}">@csrf<button class="ia-btn">{{ __('registration.create') }}</button></form>
    @else
        <p><strong>{{ __('registration.number') }}: {{ $registration->number }}</strong> · {{ __('registration.'.$registration->status) }}</p>
        @if($registration->approval_source === 'direct')<p>{{ __('registration.exception_by_reviewer') }}@if($registration->documents_waived) {{ __('registration.exception_waived') }}@endif</p>@endif
        @if($registration->review_note)<p class="mp-callout">{{ $registration->review_note }}</p>@endif
        @if($registration->status === 'approved')<p>{{ __('registration.approval_hint') }}</p>@endif
        @php($canEdit = in_array($registration->status, ['draft', 'changes_requested'], true) && app(\App\Services\CompetitionPhaseService::class)->acceptsApplications($competition))
        <section class="mp-category-section"><h2>{{ __('registration.documents') }}</h2><p>{{ __('registration.scan_hint') }}</p>
        @foreach($registration->documents->where('is_current', true)->sortBy('slot') as $document)
            <div class="mp-callout">@if($document->isTrusted())<a href="{{ route('competitions.registration.documents.show', $document) }}">{{ __('registration.document', ['slot' => $document->slot, 'version' => $document->version]) }}</a>@else<span>{{ __('registration.document', ['slot' => $document->slot, 'version' => $document->version]) }}</span>@endif <span class="registration-document-status">{{ __('registration.scan_'.$document->scanDisplayStatus()) }}</span>
            @if($canEdit)<form method="POST" action="{{ route('competitions.registration.documents.destroy', $document) }}">@csrf @method('DELETE')<input type="hidden" name="version" value="{{ $registration->version }}"><button class="ia-btn ia-btn-secondary">{{ __('registration.remove') }}</button></form>@endif</div>
        @endforeach
        </section>
        @if($canEdit)
            <form method="POST" enctype="multipart/form-data" action="{{ route('competitions.registration.upload', $registration) }}" class="mp-category-section">@csrf<input type="hidden" name="version" value="{{ $registration->version }}">
                <div class="ia-field"><label for="slot">{{ __('registration.slot') }}</label><select id="slot" name="slot" class="ia-input">@foreach(range(1,3) as $slot)<option value="{{ $slot }}">{{ $slot }}</option>@endforeach</select></div>
                <div class="ia-field"><label for="document">{{ __('registration.file') }}</label><input class="ia-input" type="file" name="document" id="document" accept="application/pdf,.pdf" required></div>
                <button class="ia-btn ia-btn-secondary">{{ __('registration.upload') }}</button>
            </form>
            <form method="POST" action="{{ route('competitions.registration.submit', $registration) }}">@csrf<input type="hidden" name="version" value="{{ $registration->version }}"><button class="ia-btn">{{ __('registration.send') }}</button></form>
        @endif
        @if($registration->documents->where('is_current', false)->isNotEmpty())<details class="mp-category-section"><summary>{{ __('registration.history') }}</summary>@foreach($registration->documents->where('is_current', false) as $document)<p>@if($document->isTrusted())<a href="{{ route('competitions.registration.documents.show', $document) }}">{{ __('registration.document', ['slot' => $document->slot, 'version' => $document->version]) }}</a>@else<span>{{ __('registration.document', ['slot' => $document->slot, 'version' => $document->version]) }}</span>@endif <span class="registration-document-status">{{ __('registration.scan_'.$document->scanDisplayStatus()) }}</span></p>@endforeach</details>@endif
    @endif
</x-uye.app-layout>
