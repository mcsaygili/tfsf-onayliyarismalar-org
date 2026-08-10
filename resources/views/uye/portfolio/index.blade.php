<x-uye.app-layout :title="__('uye.nav.portfolio')">
    <div x-data="{ uploadOpen: {{ $errors->hasAny(['photo', 'title', 'location', 'taken_at', 'tags', 'description', 'photo_category_id']) ? 'true' : 'false' }}, detailId: null, view: 'grid' }">
        <div class="ip-card" style="margin-bottom: 1.5rem;">
            <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
                <div>
                    <div class="ip-section-title">{{ __('uye.portfolio.section_title') }}</div>
                    <div class="ip-section-hint" style="margin-bottom: 0;">{{ __('uye.portfolio.section_hint') }}</div>
                </div>
                <div style="display: flex; align-items: center; gap: .9rem;">
                    <span style="font-size: .85rem; color: var(--ia-muted-dim);">{{ __('uye.portfolio.count_label', ['count' => $photos->count(), 'max' => \App\Models\Photo::MAX_PER_USER]) }}</span>
                    <div class="ip-view-toggle" role="group" aria-label="{{ __('uye.portfolio.view_toggle_label') }}">
                        <button type="button" class="ip-view-toggle-btn" :class="{ 'is-active': view === 'grid' }" @click="view = 'grid'" :aria-pressed="view === 'grid'" title="{{ __('uye.portfolio.view_grid') }}">
                            <x-uye.icon name="grid" />
                        </button>
                        <button type="button" class="ip-view-toggle-btn" :class="{ 'is-active': view === 'list' }" @click="view = 'list'" :aria-pressed="view === 'list'" title="{{ __('uye.portfolio.view_list') }}">
                            <x-uye.icon name="list" />
                        </button>
                    </div>
                    @if ($canUploadMore)
                        <button type="button" class="ia-btn ip-btn-sm" @click="uploadOpen = true">{{ __('uye.portfolio.add_photo') }}</button>
                    @endif
                </div>
            </div>
        </div>

        <div class="ip-card" style="margin-bottom: 1.5rem;">
            <form method="GET" action="{{ route('portfolio.index') }}" class="ip-grid-2" style="align-items: end;">
                <div class="ia-field" style="margin-bottom: 0;">
                    <x-uye.label for="filter_category_id" :value="__('uye.portfolio.filter_category')" />
                    <select id="filter_category_id" name="category_id" class="ia-input">
                        <option value="">{{ __('uye.portfolio.filter_all_categories') }}</option>
                        @foreach ($photoCategories as $category)
                            <option value="{{ $category->id }}" @selected($filter['category_id'] === $category->id)>{{ $category->getTranslation()?->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="ia-field" style="margin-bottom: 0;">
                    <x-uye.label for="filter_q" :value="__('uye.portfolio.filter_keyword')" />
                    <x-uye.input id="filter_q" type="text" name="q" :value="$filter['q']" autocomplete="off" />
                </div>
                <div class="ia-field" style="margin-bottom: 0;">
                    <x-uye.label for="filter_date_from_display" :value="__('uye.portfolio.filter_date_from')" />
                    <input id="filter_date_from" type="text" name="date_from" class="ia-input" value="{{ $filter['date_from'] }}" autocomplete="off"
                        x-data x-init="
                            const fp = flatpickr($el, {
                                locale: @js(app()->getLocale() === 'tr' ? 'tr' : 'default'),
                                dateFormat: 'Y-m-d', altInput: true, altInputClass: 'ia-input',
                                altFormat: @js(app()->getLocale() === 'tr' ? 'd.m.Y' : 'F j, Y'),
                                maxDate: 'today', allowInput: true, disableMobile: true,
                            });
                            fp.altInput.id = 'filter_date_from_display';
                        ">
                </div>
                <div class="ia-field" style="margin-bottom: 0;">
                    <x-uye.label for="filter_date_to_display" :value="__('uye.portfolio.filter_date_to')" />
                    <input id="filter_date_to" type="text" name="date_to" class="ia-input" value="{{ $filter['date_to'] }}" autocomplete="off"
                        x-data x-init="
                            const fp = flatpickr($el, {
                                locale: @js(app()->getLocale() === 'tr' ? 'tr' : 'default'),
                                dateFormat: 'Y-m-d', altInput: true, altInputClass: 'ia-input',
                                altFormat: @js(app()->getLocale() === 'tr' ? 'd.m.Y' : 'F j, Y'),
                                maxDate: 'today', allowInput: true, disableMobile: true,
                            });
                            fp.altInput.id = 'filter_date_to_display';
                        ">
                </div>
                <div style="display: flex; gap: .75rem; grid-column: 1 / -1;">
                    <button type="submit" class="ia-btn ip-btn-sm">{{ __('uye.portfolio.filter_apply') }}</button>
                    <a href="{{ route('portfolio.index') }}" class="ia-btn ia-btn-secondary ip-btn-sm">{{ __('uye.portfolio.filter_clear') }}</a>
                </div>
            </form>
        </div>

        @if ($photos->isEmpty())
            <div class="ip-card">
                <div class="ip-table-empty" style="padding: 2rem 0;">{{ __('uye.portfolio.empty_state') }}</div>
            </div>
        @else
            <template x-if="view === 'grid'">
                <div class="ip-photo-grid">
                    @foreach ($photos as $photo)
                        <button type="button" class="ip-photo-card" @click="detailId = '{{ $photo->id }}'">
                            <img src="{{ $photo->thumbUrl() }}" alt="{{ $photo->title }}" loading="lazy">
                            <div class="ip-photo-meta">
                                <div class="ip-photo-title">{{ $photo->title }}</div>
                                <div class="ip-photo-sub">
                                    <span>{{ $photo->taken_at->format('d.m.Y') }}</span>
                                    @if ($photo->exif_missing)
                                        <span class="ip-badge is-warning">{{ __('uye.portfolio.exif_missing_badge') }}</span>
                                    @endif
                                </div>
                            </div>
                        </button>
                    @endforeach
                </div>
            </template>

            <template x-if="view === 'list'">
                <div class="ip-table-wrap">
                    <table class="ip-table">
                        <thead>
                            <tr>
                                <th></th>
                                <th>{{ __('uye.portfolio.field_title') }}</th>
                                <th>{{ __('uye.portfolio.field_category') }}</th>
                                <th>{{ __('uye.portfolio.field_taken_at') }}</th>
                                <th>{{ __('uye.portfolio.field_tags') }}</th>
                                <th>{{ __('uye.portfolio.exif_title') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($photos as $photo)
                                <tr @click="detailId = '{{ $photo->id }}'">
                                    <td class="ip-cell-thumb"><img src="{{ $photo->thumbUrl() }}" alt="{{ $photo->title }}" loading="lazy"></td>
                                    <td class="ip-cell-name">{{ $photo->title }}</td>
                                    <td>{{ $photo->category?->getTranslation()?->name ?? '—' }}</td>
                                    <td>{{ $photo->taken_at->format('d.m.Y') }}</td>
                                    <td>{{ $photo->tags ? implode(', ', $photo->tags) : '—' }}</td>
                                    <td>
                                        @if ($photo->exif_missing)
                                            <span class="ip-badge is-warning">{{ __('uye.portfolio.exif_missing_badge') }}</span>
                                        @else
                                            <span style="color: var(--ia-muted-dim);">{{ __('uye.portfolio.exif_ok') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </template>
        @endif

        @include('uye.portfolio._upload-modal')

        @foreach ($photos as $photo)
            @include('uye.portfolio._photo-modal', ['photo' => $photo])
        @endforeach
    </div>
</x-uye.app-layout>
