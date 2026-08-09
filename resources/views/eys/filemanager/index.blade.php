@php($defaultContext = $contexts->first()['key'] ?? 'common')

<x-eys.app-layout :title="__('eys.file_manager.title')">
    <div x-data="eysFileBrowser({ context: @js($defaultContext) })">
        <div class="fm-context-tabs">
            <span style="font-size: .82rem; color: var(--ia-muted-dim); margin-right: .25rem; align-self: center;">{{ __('eys.file_manager.context') }}:</span>
            @foreach ($contexts as $c)
                <button type="button" @click="setContext(@js($c['key']))" class="ia-btn ia-btn-secondary ip-btn-sm" :style="context === @js($c['key']) ? 'border-color: var(--ia-copper-bright); color: var(--ia-cream);' : ''">{{ $c['label'] }}</button>
            @endforeach
        </div>

        <div class="ip-card">
            <x-eys.file-browser />
        </div>
    </div>

    @push('scripts')
        <script>
            window.FM = {
                browse: @json(route('eys.filemanager.browse')),
                thumb: @json(route('eys.filemanager.thumb')),
                download: @json(route('eys.filemanager.download')),
                upload: @json(route('eys.filemanager.upload')),
                folder: @json(route('eys.filemanager.folder')),
                rename: @json(route('eys.filemanager.rename')),
                delete: @json(route('eys.filemanager.delete')),
                bulkDelete: @json(route('eys.filemanager.bulkDelete')),
                move: @json(route('eys.filemanager.move')),
                csrf: @json(csrf_token()),
                lang: {
                    home: @json(__('eys.file_manager.home')),
                    files: @json(__('eys.file_manager.files')),
                    uploaded: @json(__('eys.file_manager.uploaded')),
                    deleted: @json(__('eys.file_manager.deleted')),
                    moved: @json(__('eys.file_manager.moved')),
                    copied: @json(__('eys.file_manager.copied')),
                    cut_done: @json(__('eys.file_manager.cut_done')),
                    confirm_delete: @json(__('eys.file_manager.confirm_delete')),
                    confirm_delete_n: @json(__('eys.file_manager.confirm_delete_n')),
                    error: @json(__('eys.file_manager.error')),
                },
            };
        </script>
        <script src="{{ asset('js/eys-filemanager.js') }}"></script>
    @endpush
</x-eys.app-layout>
