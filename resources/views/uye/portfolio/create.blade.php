{{-- $photoCategories, $userEquipment dışarıdan geliyor (PortfolioController::create()) --}}
<x-uye.app-layout :title="__('uye.portfolio.add_photo')">
    <div class="ip-page-actions">
        <a href="{{ route('portfolio.index') }}" class="ia-btn ia-btn-secondary ip-btn-sm">
            <x-uye.icon name="back" />
            {{ __('uye.portfolio.back_to_list') }}
        </a>
    </div>

    <div class="ip-card">
        <div class="ip-section-title">{{ __('uye.portfolio.add_photo') }}</div>
        <div class="ip-section-hint">{{ __('uye.portfolio.create_hint') }}</div>

        <form method="POST" action="{{ route('portfolio.store') }}" enctype="multipart/form-data" novalidate autocomplete="off">
            @csrf

            <div class="ia-field">
                <x-uye.label for="photo" :value="__('uye.portfolio.field_photo')" />
                <input id="photo" type="file" name="photo" class="ia-input" accept="image/jpeg,image/png,image/webp" required>
                <x-uye.input-error :messages="$errors->get('photo')" />
            </div>

            <div class="ip-grid-2">
                <div class="ia-field">
                    <x-uye.label for="title" :value="__('uye.portfolio.field_title')" />
                    <x-uye.input id="title" type="text" name="title" :value="old('title')" autocomplete="off" required />
                    <x-uye.input-error :messages="$errors->get('title')" />
                </div>
                <div class="ia-field">
                    <x-uye.label for="location" :value="__('uye.portfolio.field_location')" />
                    <x-uye.input id="location" type="text" name="location" :value="old('location')" autocomplete="off" required />
                    <x-uye.input-error :messages="$errors->get('location')" />
                </div>
            </div>

            <div class="ip-grid-2">
                <div class="ia-field">
                    <x-uye.label for="taken_at_display" :value="__('uye.portfolio.field_taken_at')" />
                    <input id="taken_at" type="text" name="taken_at" class="ia-input" value="{{ old('taken_at') }}" autocomplete="off" required
                        x-data x-init="
                            const fp = flatpickr($el, {
                                locale: @js(app()->getLocale() === 'tr' ? 'tr' : 'default'),
                                dateFormat: 'Y-m-d', altInput: true, altInputClass: 'ia-input',
                                altFormat: @js(app()->getLocale() === 'tr' ? 'd.m.Y' : 'F j, Y'),
                                maxDate: 'today', allowInput: true, disableMobile: true,
                            });
                            fp.altInput.id = 'taken_at_display';
                        ">
                    <x-uye.input-error :messages="$errors->get('taken_at')" />
                </div>
                <div class="ia-field">
                    <x-uye.label for="photo_category_id" :value="__('uye.portfolio.field_category')" />
                    <select id="photo_category_id" name="photo_category_id" class="ia-input">
                        <option value="">{{ __('uye.portfolio.category_none') }}</option>
                        @foreach ($photoCategories as $category)
                            <option value="{{ $category->id }}" @selected(old('photo_category_id') === $category->id)>{{ $category->getTranslation()?->name }}</option>
                        @endforeach
                    </select>
                    <x-uye.input-error :messages="$errors->get('photo_category_id')" />
                </div>
            </div>

            <div class="ia-field">
                <x-uye.label for="tags" :value="__('uye.portfolio.field_tags')" />
                <x-uye.input id="tags" type="text" name="tags" :value="old('tags')" autocomplete="off" :placeholder="__('uye.portfolio.field_tags_hint')" />
                <x-uye.input-error :messages="$errors->get('tags')" />
            </div>

            <div class="ia-field">
                <x-uye.label for="description" :value="__('uye.portfolio.field_description')" />
                <textarea id="description" name="description" class="ia-input" rows="3">{{ old('description') }}</textarea>
                <x-uye.input-error :messages="$errors->get('description')" />
            </div>

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
                                <input type="checkbox" name="equipment[]" value="{{ $item->id }}" @checked(in_array($item->id, old('equipment', [])))>
                                {{ $item->equipmentModel->brand?->name }} {{ $item->equipmentModel->name }}
                            </label>
                        @endforeach
                    </div>
                @endif
                <x-uye.input-error :messages="$errors->get('equipment')" />
            </div>

            <div style="margin-top: 1.5rem;">
                <x-uye.button>{{ __('uye.portfolio.save') }}</x-uye.button>
            </div>
        </form>
    </div>
</x-uye.app-layout>
