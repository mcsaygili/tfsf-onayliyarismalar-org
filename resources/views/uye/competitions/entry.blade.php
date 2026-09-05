<x-uye.app-layout :title="__('uye.competitions.entry_manage')">
    <a class="mp-back" href="{{ route('competitions.show', $entry->competition) }}">← {{ $entry->competition->name }}</a>
    <header class="mp-page-heading"><div><h1>{{ __('uye.competitions.entry_manage') }}</h1><p>{{ __('uye.competitions.entry_manage_hint') }}</p></div><span class="mp-state is-{{ $entry->status->value }}">{{ __('uye.competitions.entry_status.'.$entry->status->value) }}</span></header>

    @if($errors->any())<div class="mp-callout is-error"><strong>{{ __('uye.competitions.errors.title') }}</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    @if($editable)
        <section class="mp-entry-step">
            <header><span>1</span><div><h2>{{ __('uye.competitions.select_category') }}</h2><p>{{ __('uye.competitions.select_category_hint') }}</p></div></header>
            <form method="POST" action="{{ route('competitions.entry.categories.store', $entry) }}" class="mp-inline-form">@csrf
                <select name="category_id" class="ia-input" required><option value="">{{ __('uye.competitions.choose_category') }}</option>@foreach($entry->competition->categories as $category)<option value="{{ $category->id }}" @disabled($entry->submissions->contains('competition_category_id', $category->id))>{{ $category->name }}</option>@endforeach</select>
                <button class="ia-btn">{{ __('uye.competitions.add_category') }}</button>
            </form>
        </section>
    @endif

    @foreach($entry->submissions as $submission)
        @php
            $activePhotos = $submission->photos->whereNull('withdrawn_at');
            $canModifyPhotos = (bool) $submissionMutability->get($submission->id, false);
        @endphp
        <section class="mp-entry-step">
            <header><span>{{ $loop->iteration + 1 }}</span><div><h2>{{ $submission->category->name }}</h2><p>{{ trans_choice('uye.competitions.photo_usage', $activePhotos->count(), ['used' => $activePhotos->count(), 'max' => $submission->category->max_photos_per_participant]) }}</p></div><span class="mp-state is-{{ $submission->status->value }}">{{ __('uye.competitions.entry_status.'.$submission->status->value) }}</span></header>
            <p class="mp-callout">{{ \App\Support\Photo\CategoryPhotoRules::summary($submission->category->photo_rules) }}</p>
            <div class="mp-selected-photos">
                @foreach($activePhotos as $photo)
                    <article><img src="{{ route('competitions.photos.show', $photo) }}" alt=""><div><strong>{{ __('uye.competitions.work_number', ['number' => $loop->iteration]) }}</strong><span>{{ $photo->captureDevice?->name }}</span></div>
                        @if($canModifyPhotos)<form method="POST" action="{{ route('competitions.submission.photos.destroy', $photo) }}">@csrf @method('DELETE')<button type="submit">{{ $editable ? __('uye.common.delete_action') : __('uye.competitions.withdraw_photo') }}</button></form>@endif
                        @foreach($scorecards[$photo->id] ?? [] as $scorecard)
                            <div class="mp-scorecard">
                                <div><strong>{{ __('uye.competitions.scorecard_title', ['round' => $scorecard['round_number']]) }}</strong><span>{{ __('uye.competitions.scorecard_average', ['score' => number_format($scorecard['average'], 2, ',', '.')]) }}</span></div>
                                <div class="mp-scorecard-values">@foreach($scorecard['scores'] as $score)<span>{{ $score['label'] }} <b>{{ $score['score'] }}</b></span>@endforeach</div>
                                <small>{{ __('uye.competitions.scorecard_privacy') }}</small>
                            </div>
                        @endforeach
                    </article>
                @endforeach
            </div>

            @include('uye.competitions.partials.declarations')

            @if($canModifyPhotos && !$editable)
                <div class="mp-callout is-warning"><strong>{{ __('uye.competitions.evaluation_revision_title') }}</strong><p>{{ __('uye.competitions.evaluation_revision_hint', ['date' => $entry->competition->competition_ends_at?->format('d.m.Y H:i')]) }}</p></div>
            @endif

            @if($canModifyPhotos && $activePhotos->count() < $submission->category->max_photos_per_participant)
                <div class="mp-photo-sources">
                    <form method="POST" action="{{ route('competitions.submission.portfolio.store', $submission) }}">@csrf
                        <h3>{{ __('uye.competitions.from_portfolio') }}</h3><p>{{ __('declarations.portfolio_hint') }}</p>
                        <select name="photo_id" class="ia-input" required><option value="">{{ __('uye.competitions.choose_photo') }}</option>@foreach($portfolioPhotos as $photo)<option value="{{ $photo->id }}">{{ $photo->title }}</option>@endforeach</select>
                        @include('uye.competitions.partials.photo-rules')
                        @if($submission->category->photo_story_required)<label>{{ __('declarations.story') }}<textarea class="ia-input" name="declaration[story]" rows="3" maxlength="4000"></textarea></label>@endif
                        <button class="ia-btn ia-btn-secondary">{{ __('uye.competitions.add_photo') }}</button>
                    </form>
                    <form method="POST" enctype="multipart/form-data" action="{{ route('competitions.submission.upload', $submission) }}">@csrf
                        <h3>{{ __('uye.competitions.upload_new') }}</h3>
                        <input type="file" name="photo" class="ia-input" accept="{{ collect(\App\Support\Photo\CategoryPhotoRules::normalize($submission->category->photo_rules)['formats'])->map(fn ($format) => 'image/'.$format)->join(',') }}" required>
                        @include('uye.competitions.partials.photo-rules')
                        @include('uye.competitions.partials.upload-declaration')
                        <button class="ia-btn ia-btn-secondary">{{ __('uye.competitions.upload_and_add') }}</button>
                    </form>
                </div>
            @endif

            @if($submission->approvals->isNotEmpty())
                <div class="mp-approval-line">@foreach($submission->approvals as $approval)<span>{{ __('uye.competitions.approval_types.'.$approval->approval_type) }}: <strong>{{ __('uye.competitions.approval_status.'.$approval->status->value) }}</strong></span>@endforeach</div>
            @endif
        </section>
    @endforeach

    @if($editable)
        <form method="POST" action="{{ route('competitions.entry.submit', $entry) }}" class="mp-submit-panel">@csrf
            <label><input type="checkbox" name="consent" value="1" required> <span>{{ __('uye.competitions.consent') }}</span></label>
            <button class="ia-btn">{{ __('uye.competitions.submit_entry') }}</button>
        </form>
    @elseif(!$entry->status->isEditable())
        <div class="mp-callout is-success"><strong>{{ __('uye.competitions.entry_received') }}</strong><p>{{ __('uye.competitions.entry_received_hint') }}</p></div>
    @else
        <div class="mp-callout is-warning"><strong>{{ __('uye.competitions.entry_closed') }}</strong><p>{{ __('uye.competitions.violations.applications_not_open') }}</p></div>
    @endif
</x-uye.app-layout>
