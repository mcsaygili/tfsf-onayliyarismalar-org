<x-uye.app-layout :title="__('uye.nav.equipment')">
    <div class="ip-card" style="margin-bottom: 1.5rem;">
        <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
            <div>
                <div class="ip-section-title">{{ __('uye.equipment.section_title') }}</div>
                <div class="ip-section-hint" style="margin-bottom: 0;">{{ __('uye.equipment.section_hint') }}</div>
            </div>
            <a href="{{ route('equipment.create') }}" class="ia-btn ip-btn-sm">{{ __('uye.equipment.add_equipment') }}</a>
        </div>
    </div>

    @if ($equipment->isEmpty())
        <div class="ip-card">
            <div class="ip-table-empty" style="padding: 2rem 0;">{{ __('uye.equipment.empty_state') }}</div>
        </div>
    @else
        <div class="ip-table-wrap">
            <table class="ip-table">
                <thead>
                    <tr>
                        <th>{{ __('uye.equipment.column_type') }}</th>
                        <th>{{ __('uye.equipment.column_brand') }}</th>
                        <th>{{ __('uye.equipment.column_model') }}</th>
                        <th>{{ __('uye.equipment.column_notes') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($equipment as $item)
                        <tr>
                            <td>{{ $item->equipmentModel->type?->getTranslation()?->name ?? '—' }}</td>
                            <td>{{ $item->equipmentModel->brand?->name ?? '—' }}</td>
                            <td class="ip-cell-name">{{ $item->equipmentModel->name }}</td>
                            <td>{{ $item->notes ?: '—' }}</td>
                            <td style="text-align: right; white-space: nowrap;">
                                <a href="{{ route('equipment.edit', $item) }}" class="ip-row-icon-btn" title="{{ __('uye.common.edit_action') }}" aria-label="{{ __('uye.common.edit_action') }}">
                                    <x-uye.icon name="edit" />
                                </a>
                                <form method="POST" action="{{ route('equipment.destroy', $item) }}" style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="ip-row-icon-btn" title="{{ __('uye.common.delete_action') }}" aria-label="{{ __('uye.common.delete_action') }}" onclick="uyeConfirm(@js(__('uye.equipment.delete_confirm_text')), this.closest('form'))">
                                        <x-uye.icon name="trash" />
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if (session('status'))
        <script>
            document.addEventListener('DOMContentLoaded', () => window.uyeToast('success', @js(session('status'))));
        </script>
    @endif
</x-uye.app-layout>
