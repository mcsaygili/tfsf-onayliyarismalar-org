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
        <section class="mp-entry-step">
            <header><span>{{ $loop->iteration + 1 }}</span><div><h2>{{ $submission->category->name }}</h2><p>{{ trans_choice('uye.competitions.photo_usage', $submission->photos->count(), ['used' => $submission->photos->count(), 'max' => $submission->category->max_photos_per_participant]) }}</p></div><span class="mp-state is-{{ $submission->status->value }}">{{ __('uye.competitions.entry_status.'.$submission->status->value) }}</span></header>
            <div class="mp-selected-photos">
                @foreach($submission->photos as $photo)
                    <article><img src="{{ route('competitions.photos.show', $photo) }}" alt=""><div><strong>{{ __('uye.competitions.work_number', ['number' => $loop->iteration]) }}</strong><span>{{ $photo->captureDevice?->name }}</span></div>
                        @if($editable)<form method="POST" action="{{ route('competitions.submission.photos.destroy', $photo) }}">@csrf @method('DELETE')<button type="submit">{{ __('uye.common.delete_action') }}</button></form>@endif
                    </article>
                @endforeach
            </div>

            @if($editable && $submission->photos->count() < $submission->category->max_photos_per_participant)
                <div class="mp-photo-sources">
                    <form method="POST" action="{{ route('competitions.submission.portfolio.store', $submission) }}">@csrf
                        <h3>{{ __('uye.competitions.from_portfolio') }}</h3>
                        <select name="photo_id" class="ia-input" required><option value="">{{ __('uye.competitions.choose_photo') }}</option>@foreach($portfolioPhotos as $photo)<option value="{{ $photo->id }}">{{ $photo->title }}</option>@endforeach</select>
                        @include('uye.competitions.partials.photo-rules')
                        <button class="ia-btn ia-btn-secondary">{{ __('uye.competitions.add_photo') }}</button>
                    </form>
                    <form method="POST" enctype="multipart/form-data" action="{{ route('competitions.submission.upload', $submission) }}">@csrf
                        <h3>{{ __('uye.competitions.upload_new') }}</h3>
                        <input type="file" name="photo" class="ia-input" accept="image/jpeg,image/png,image/webp" required>
                        @include('uye.competitions.partials.photo-rules')
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
