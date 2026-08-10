{{-- $photo, $photoCategories, $userEquipment dışarıdan geliyor (PortfolioController::edit()) --}}
<x-uye.app-layout :title="__('uye.portfolio.edit_title')">
    <div class="ip-page-actions">
        <a href="{{ route('portfolio.index') }}" class="ia-btn ia-btn-secondary ip-btn-sm">
            <x-uye.icon name="back" />
            {{ __('uye.portfolio.back_to_list') }}
        </a>
    </div>

    <img src="{{ $photo->url() }}" alt="{{ $photo->title }}" style="width: 100%; max-height: 360px; object-fit: contain; border-radius: 10px; background: var(--ia-bg); margin-bottom: 1.25rem;">

    <div class="ip-card" style="margin-bottom: 1.25rem;">
        <div class="ip-section-title" style="font-size: .92rem;">{{ __('uye.portfolio.exif_title') }}</div>
        @if ($photo->exif_missing)
            <div class="ip-alert ip-alert-warning" style="margin-bottom: 0;">
                <x-uye.icon name="warning" />
                <div>
                    <div class="ip-alert-title">{{ __('uye.portfolio.exif_missing_badge') }}</div>
                    <div class="ip-alert-text">{{ __('uye.portfolio.exif_missing_hint') }}</div>
                </div>
            </div>
        @else
            <dl class="ip-exif-grid">
                <dt>{{ __('uye.portfolio.exif_camera_make') }}</dt>
                <dd>{{ $photo->camera_make ?: __('uye.portfolio.exif_not_available') }}</dd>
                <dt>{{ __('uye.portfolio.exif_camera_model') }}</dt>
                <dd>{{ $photo->camera_model ?: __('uye.portfolio.exif_not_available') }}</dd>
                <dt>{{ __('uye.portfolio.exif_lens') }}</dt>
                <dd>{{ $photo->lens ?: __('uye.portfolio.exif_not_available') }}</dd>
                <dt>{{ __('uye.portfolio.exif_focal_length') }}</dt>
                <dd>{{ $photo->focal_length ?: __('uye.portfolio.exif_not_available') }}</dd>
                <dt>{{ __('uye.portfolio.exif_aperture') }}</dt>
                <dd>{{ $photo->aperture ?: __('uye.portfolio.exif_not_available') }}</dd>
                <dt>{{ __('uye.portfolio.exif_shutter_speed') }}</dt>
                <dd>{{ $photo->shutter_speed ?: __('uye.portfolio.exif_not_available') }}</dd>
                <dt>{{ __('uye.portfolio.exif_iso') }}</dt>
                <dd>{{ $photo->iso ?: __('uye.portfolio.exif_not_available') }}</dd>
                <dt>{{ __('uye.portfolio.exif_captured_at') }}</dt>
                <dd>{{ $photo->exif_captured_at?->format('d.m.Y H:i') ?: __('uye.portfolio.exif_not_available') }}</dd>
                <dt>{{ __('uye.portfolio.exif_color_space') }}</dt>
                <dd>{{ $photo->color_space ?: __('uye.portfolio.exif_not_available') }}</dd>
                <dt>{{ __('uye.portfolio.exif_resolution') }}</dt>
                <dd>{{ $photo->width && $photo->height ? "{$photo->width} × {$photo->height}" : __('uye.portfolio.exif_not_available') }}</dd>
                <dt>{{ __('uye.portfolio.exif_file_size') }}</dt>
                <dd>{{ number_format($photo->file_size_bytes / 1024, 0) }} KB</dd>
            </dl>
        @endif
    </div>

    <div class="ip-card" style="margin-bottom: 1.25rem;">
        <div class="ip-section-title" style="font-size: .92rem;">{{ __('uye.portfolio.equipment_used_title') }}</div>
        @if ($photo->equipment->isEmpty())
            <div style="font-size: .82rem; color: var(--ia-muted-dim);">{{ __('uye.portfolio.equipment_used_empty') }}</div>
        @else
            <div class="ip-tag-list">
                @foreach ($photo->equipment as $item)
                    <span class="ip-tag">{{ $item->equipmentModel->brand?->name }} {{ $item->equipmentModel->name }}</span>
                @endforeach
            </div>
        @endif
    </div>

    <div class="ip-card">
        <div class="ip-section-title">{{ __('uye.portfolio.edit_title') }}</div>

        <form method="POST" action="{{ route('portfolio.update', $photo) }}" novalidate autocomplete="off">
            @csrf
            @method('PATCH')

            <div class="ip-grid-2">
                <div class="ia-field">
                    <x-uye.label for="title" :value="__('uye.portfolio.field_title')" />
                    <x-uye.input id="title" type="text" name="title" :value="$photo->title" autocomplete="off" required />
                </div>
                <div class="ia-field">
                    <x-uye.label for="location" :value="__('uye.portfolio.field_location')" />
                    <x-uye.input id="location" type="text" name="location" :value="$photo->location" autocomplete="off" required />
                </div>
            </div>

            <div class="ip-grid-2">
                <div class="ia-field">
                    <x-uye.label for="taken_at_display" :value="__('uye.portfolio.field_taken_at')" />
                    <input id="taken_at" type="text" name="taken_at" class="ia-input" value="{{ $photo->taken_at->format('Y-m-d') }}" autocomplete="off" required
                        x-data x-init="
                            const fp = flatpickr($el, {
                                locale: @js(app()->getLocale() === 'tr' ? 'tr' : 'default'),
                                dateFormat: 'Y-m-d', altInput: true, altInputClass: 'ia-input',
                                altFormat: @js(app()->getLocale() === 'tr' ? 'd.m.Y' : 'F j, Y'),
                                maxDate: 'today', allowInput: true, disableMobile: true,
                            });
                            fp.altInput.id = 'taken_at_display';
                        ">
                </div>
                <div class="ia-field">
                    <x-uye.label for="photo_category_id" :value="__('uye.portfolio.field_category')" />
                    <select id="photo_category_id" name="photo_category_id" class="ia-input">
                        <option value="">{{ __('uye.portfolio.category_none') }}</option>
                        @foreach ($photoCategories as $category)
                            <option value="{{ $category->id }}" @selected($photo->photo_category_id === $category->id)>{{ $category->getTranslation()?->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="ia-field">
                <x-uye.label for="tags" :value="__('uye.portfolio.field_tags')" />
                <x-uye.input id="tags" type="text" name="tags" :value="implode(', ', $photo->tags ?? [])" autocomplete="off" :placeholder="__('uye.portfolio.field_tags_hint')" />
            </div>

            <div class="ia-field">
                <x-uye.label for="description" :value="__('uye.portfolio.field_description')" />
                <textarea id="description" name="description" class="ia-input" rows="3">{{ $photo->description }}</textarea>
            </div>

            @php $taggedIds = $photo->equipment->pluck('id')->all(); @endphp
            <div class="ia-field" style="margin-bottom: 0;">
                <x-uye.label :value="__('uye.portfolio.field_equipment')" />
                @if ($userEquipment->isEmpty())
                    <div style="font-size: .82rem; color: var(--ia-muted-dim);">
                        {{ __('uye.portfolio.equipment_empty') }}
                        <a href="{{ route('equipment.index') }}" style="color: var(--ia-copper);">{{ __('uye.portfolio.equipment_empty_cta') }}</a>
                    </div>
                @else
                    <div class="ip-checklist">
                        @foreach ($userEquipment as $item)
                            <label class="ip-checklist-item">
                                <input type="checkbox" name="equipment[]" value="{{ $item->id }}" @checked(in_array($item->id, $taggedIds))>
                                {{ $item->equipmentModel->brand?->name }} {{ $item->equipmentModel->name }}
                            </label>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="ip-modal-actions" style="justify-content: space-between; margin-top: 1.5rem;">
                <button type="button" class="ia-btn ia-btn-danger" onclick="uyeConfirm(@js(__('uye.portfolio.delete_confirm_text')), document.getElementById('delete-photo-{{ $photo->id }}'))">
                    <x-uye.icon name="trash" />
                    {{ __('uye.portfolio.delete_photo') }}
                </button>

                <x-uye.button>{{ __('uye.portfolio.save') }}</x-uye.button>
            </div>
        </form>

        <form id="delete-photo-{{ $photo->id }}" method="POST" action="{{ route('portfolio.destroy', $photo) }}" style="display: none;">
            @csrf
            @method('DELETE')
        </form>
    </div>
</x-uye.app-layout>
