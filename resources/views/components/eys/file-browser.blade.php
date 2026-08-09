{{-- Dosya Yöneticisi tarayıcı gövdesi. Üst kapsayıcı x-data="eysFileBrowser(...)" sağlamalıdır. --}}
<div class="fm" @dragover.prevent="dragover = true" @dragleave.prevent="dragover = false" @drop.prevent="onDrop($event)">

    <div class="fm-toolbar">
        <label class="ia-btn ip-btn-sm" style="cursor: pointer;">
            <x-eys.icon name="upload" style="width: 16px; height: 16px;" /> {{ __('eys.file_manager.upload') }}
            <input type="file" multiple style="display: none;" @change="uploadFiles($event)">
        </label>
        <button type="button" @click="openFolderDialog()" class="ia-btn ia-btn-secondary ip-btn-sm">
            <x-eys.icon name="folder" style="width: 16px; height: 16px;" /> {{ __('eys.file_manager.new_folder') }}
        </button>
        <button type="button" x-show="path" x-cloak @click="up()" class="ia-btn ia-btn-secondary ip-btn-sm">
            <x-eys.icon name="arrow-up" style="width: 16px; height: 16px;" /> {{ __('eys.file_manager.up') }}
        </button>
        <button type="button" x-show="clipboard.length" x-cloak @click="pasteHere()" class="ia-btn ia-btn-secondary ip-btn-sm">
            <x-eys.icon name="copy" style="width: 16px; height: 16px;" /> <span x-text="@js(__('eys.file_manager.paste_here')) + ' (' + clipboard.length + ')'"></span>
        </button>
        <div class="fm-spacer"></div>
        <input type="search" x-model="q" @keydown.enter="runSearch()" placeholder="{{ __('eys.file_manager.search') }}" class="ia-input">
        <button type="button" @click="runSearch()" class="ia-btn ia-btn-secondary ip-btn-sm">{{ __('eys.file_manager.apply') }}</button>
        <label style="display: inline-flex; align-items: center; gap: .4rem; font-size: .82rem; color: var(--ia-muted-dim); cursor: pointer;">
            <input type="checkbox" :checked="recursive" @change="toggleRecursive()"> {{ __('eys.file_manager.recursive') }}
        </label>
        <div style="display: inline-flex; border: 1px solid var(--ia-surface-border); border-radius: 8px; overflow: hidden;">
            <button type="button" @click="view='grid'" class="fm-icon-btn" :style="view==='grid' ? 'color: var(--ia-copper-bright);' : ''"><x-eys.icon name="grid" style="width: 16px; height: 16px;" /></button>
            <button type="button" @click="view='list'" class="fm-icon-btn" :style="view==='list' ? 'color: var(--ia-copper-bright);' : ''" style="border-left: 1px solid var(--ia-surface-border);"><x-eys.icon name="layers" style="width: 16px; height: 16px;" /></button>
        </div>
    </div>

    <div x-show="selected.length" x-cloak class="fm-selectbar">
        <span x-text="@js(__('eys.file_manager.selected_n')).replace(':count', selected.length)" style="font-weight: 700; color: var(--ia-copper-bright);"></span>
        <button type="button" @click="cut()" class="ia-btn ia-btn-secondary ip-btn-sm">{{ __('eys.file_manager.cut') }}</button>
        <button type="button" @click="bulkDelete()" class="ia-btn"><x-eys.icon name="trash" style="width: 14px; height: 14px;" /> {{ __('eys.file_manager.delete') }}</button>
        <button type="button" @click="selected=[]" class="ia-btn">{{ __('eys.file_manager.cancel') }}</button>
        <div class="fm-spacer"></div>
        <button type="button" @click="selectAllVisible()" class="ia-btn ia-btn-secondary ip-btn-sm">{{ __('eys.file_manager.select_all') }}</button>
    </div>

    <nav class="fm-crumbs">
        <template x-for="(c, i) in crumbs" :key="i">
            <span style="display: inline-flex; align-items: center; gap: .25rem;">
                <button type="button" @click="load(c.path)" x-text="c.name"></button>
                <span x-show="i < crumbs.length - 1" style="opacity: .5;">/</span>
            </span>
        </template>
        <span x-show="recursive" style="margin-left: .5rem; color: var(--ia-copper-bright);">({{ __('eys.file_manager.recursive') }})</span>
    </nav>

    <div x-show="upload.active" x-cloak style="margin-bottom: .9rem;">
        <div style="display: flex; justify-content: space-between; font-size: .74rem; color: var(--ia-muted-dim); margin-bottom: .3rem;"><span x-text="upload.label"></span><span x-text="upload.pct + '%'"></span></div>
        <div class="fm-progress-track"><div class="fm-progress-fill" :style="'width:' + upload.pct + '%'"></div></div>
    </div>

    <div x-show="loading" class="fm-empty">…</div>

    <div x-show="!loading">
        <div x-show="folders.length === 0 && files.length === 0" class="fm-empty">{{ __('eys.file_manager.empty') }}</div>

        {{-- GRID --}}
        <div x-show="view==='grid' && (folders.length || files.length)" class="fm-grid">
            <template x-for="f in folders" :key="'d'+f.path">
                <div class="fm-folder-tile" :class="isSelected(f.path) && 'is-selected'">
                    <input type="checkbox" :checked="isSelected(f.path)" @change="toggleSelect(f)">
                    <button type="button" @click="load(f.path)" class="fm-open">
                        <x-eys.icon name="folder" style="width: 18px; height: 18px; color: var(--ia-copper); flex-shrink: 0;" />
                        <span class="fm-name" x-text="f.name"></span>
                    </button>
                    <button type="button" @click="openRenameDialog(f)" class="fm-icon-btn"><x-eys.icon name="edit" style="width: 13px; height: 13px;" /></button>
                    <button type="button" @click="removeItem(f)" class="fm-icon-btn is-danger"><x-eys.icon name="trash" style="width: 13px; height: 13px;" /></button>
                </div>
            </template>

            <template x-for="f in files" :key="'f'+f.path">
                <div class="fm-file-tile" :class="isSelected(f.path) && 'is-selected'">
                    <div class="fm-file-thumb" @click="f.isImage && openLightbox(f)">
                        <input type="checkbox" :checked="isSelected(f.path)" @change="toggleSelect(f)" @click.stop>
                        <template x-if="f.isImage"><img :src="f.thumb" :alt="f.name" loading="lazy"></template>
                        <template x-if="!f.isImage"><span class="fm-ext" x-text="f.ext"></span></template>
                    </div>
                    <div class="fm-file-body">
                        <div class="fm-meta">
                            <span class="fm-name" x-text="f.name"></span>
                            <span class="fm-size" x-text="fmtSize(f.size)"></span>
                        </div>
                        <button type="button" @click="openDetails(f)" title="{{ __('eys.file_manager.details') }}" class="fm-icon-btn"><x-eys.icon name="grid" style="width: 13px; height: 13px;" /></button>
                        <button type="button" @click="download(f)" title="{{ __('eys.file_manager.download') }}" class="fm-icon-btn"><x-eys.icon name="upload" style="width: 13px; height: 13px; transform: rotate(180deg);" /></button>
                        <button type="button" @click="copyUrl(f)" title="{{ __('eys.file_manager.copy_url') }}" class="fm-icon-btn"><x-eys.icon name="copy" style="width: 13px; height: 13px;" /></button>
                        <button type="button" @click="openRenameDialog(f)" class="fm-icon-btn"><x-eys.icon name="edit" style="width: 13px; height: 13px;" /></button>
                        <button type="button" @click="removeItem(f)" class="fm-icon-btn is-danger"><x-eys.icon name="trash" style="width: 13px; height: 13px;" /></button>
                    </div>
                </div>
            </template>
        </div>

        {{-- LIST --}}
        <div x-show="view==='list' && (folders.length || files.length)" class="fm-list">
            <template x-for="f in folders" :key="'dl'+f.path">
                <div class="fm-list-row">
                    <input type="checkbox" :checked="isSelected(f.path)" @change="toggleSelect(f)">
                    <button type="button" @click="load(f.path)" style="display: flex; align-items: center; gap: .6rem; flex: 1; min-width: 0; text-align: left; background: none; border: none; cursor: pointer; color: inherit;">
                        <x-eys.icon name="folder" style="width: 16px; height: 16px; color: var(--ia-copper); flex-shrink: 0;" />
                        <span class="fm-name" x-text="f.name"></span>
                    </button>
                    <button type="button" @click="openRenameDialog(f)" class="fm-icon-btn"><x-eys.icon name="edit" style="width: 14px; height: 14px;" /></button>
                    <button type="button" @click="removeItem(f)" class="fm-icon-btn is-danger"><x-eys.icon name="trash" style="width: 14px; height: 14px;" /></button>
                </div>
            </template>
            <template x-for="f in files" :key="'fl'+f.path">
                <div class="fm-list-row">
                    <input type="checkbox" :checked="isSelected(f.path)" @change="toggleSelect(f)">
                    <template x-if="f.isImage"><img :src="f.thumb" loading="lazy" class="fm-thumb-sm" style="cursor: pointer;" @click="openLightbox(f)"></template>
                    <template x-if="!f.isImage"><span class="fm-ext-sm" x-text="f.ext"></span></template>
                    <span class="fm-name" x-text="f.name"></span>
                    <span style="font-size: .74rem; color: var(--ia-muted-dim);" x-text="fmtSize(f.size)"></span>
                    <button type="button" @click="download(f)" class="fm-icon-btn"><x-eys.icon name="upload" style="width: 14px; height: 14px; transform: rotate(180deg);" /></button>
                    <button type="button" @click="copyUrl(f)" class="fm-icon-btn"><x-eys.icon name="copy" style="width: 14px; height: 14px;" /></button>
                    <button type="button" @click="openRenameDialog(f)" class="fm-icon-btn"><x-eys.icon name="edit" style="width: 14px; height: 14px;" /></button>
                    <button type="button" @click="removeItem(f)" class="fm-icon-btn is-danger"><x-eys.icon name="trash" style="width: 14px; height: 14px;" /></button>
                </div>
            </template>
        </div>

        <div x-show="pageCount > 1" class="fm-pagination">
            <button type="button" @click="goPage(page-1)" :disabled="page<=1" class="ia-btn ia-btn-secondary ip-btn-sm">{{ __('eys.file_manager.prev') }}</button>
            <span x-text="@js(__('eys.file_manager.page_of')).replace(':page', page).replace(':total', pageCount)"></span>
            <button type="button" @click="goPage(page+1)" :disabled="page>=pageCount" class="ia-btn ia-btn-secondary ip-btn-sm">{{ __('eys.file_manager.next') }}</button>
        </div>
    </div>

    <div x-show="dragover" x-cloak class="fm-dropzone">
        <span>{{ __('eys.file_manager.drop_here') }}</span>
    </div>

    {{-- Klasör / yeniden adlandır diyaloğu --}}
    <div x-show="dialog.open" x-cloak class="fm-overlay" @keydown.escape.window="dialog.open=false">
        <div class="fm-overlay-bg" @click="dialog.open=false"></div>
        <div class="fm-dialog">
            <h3 x-text="dialog.mode === 'folder' ? @js(__('eys.file_manager.new_folder')) : @js(__('eys.file_manager.new_name'))"></h3>
            <input type="text" x-ref="dialogInput" x-model="dialog.name" @keydown.enter.prevent="submitDialog()" class="ia-input" style="width: 100%;">
            <div class="fm-dialog-actions">
                <button type="button" @click="dialog.open=false" class="ia-btn">{{ __('eys.file_manager.cancel') }}</button>
                <button type="button" @click="submitDialog()" class="ia-btn">{{ __('eys.file_manager.save') }}</button>
            </div>
        </div>
    </div>

    {{-- Onay kutusu --}}
    <div x-show="confirmBox.open" x-cloak class="fm-overlay" @keydown.escape.window="confirmBox.open=false">
        <div class="fm-overlay-bg" @click="confirmBox.open=false"></div>
        <div class="fm-dialog">
            <p style="font-size: .85rem; color: var(--ia-muted); margin-bottom: 1rem;" x-text="confirmBox.msg"></p>
            <div class="fm-dialog-actions">
                <button type="button" @click="confirmBox.open=false" class="ia-btn">{{ __('eys.file_manager.cancel') }}</button>
                <button type="button" @click="confirmYes()" class="ia-btn">{{ __('eys.file_manager.delete') }}</button>
            </div>
        </div>
    </div>

    {{-- Lightbox --}}
    <div x-show="lightbox.open" x-cloak class="fm-lightbox" @click="lightbox.open=false" @keydown.escape.window="lightbox.open=false">
        <button type="button" @click="lightbox.open=false" class="fm-lightbox-close">&times;</button>
        <div style="text-align: center;">
            <img :src="lightbox.url">
            <p x-text="lightbox.name"></p>
        </div>
    </div>

    {{-- Detay paneli --}}
    <div x-show="details.open" x-cloak class="fm-overlay" @keydown.escape.window="details.open=false">
        <div class="fm-overlay-bg" @click="details.open=false"></div>
        <div class="fm-dialog" style="max-width: 420px;" x-show="details.file">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: .8rem;">
                <h3 style="margin: 0;" x-text="details.file?.name"></h3>
                <button type="button" @click="details.open=false" class="fm-icon-btn"><x-eys.icon name="close" style="width: 16px; height: 16px;" /></button>
            </div>
            <template x-if="details.file?.isImage">
                <img :src="details.file.url" style="width: 100%; max-height: 190px; object-fit: contain; border-radius: 8px; background: var(--ia-bg); margin-bottom: .8rem;">
            </template>
            <dl style="font-size: .82rem; display: flex; flex-direction: column; gap: .4rem;">
                <div style="display: flex; justify-content: space-between; gap: .75rem;"><dt style="color: var(--ia-muted-dim);">{{ __('eys.file_manager.size') }}</dt><dd style="color: var(--ia-cream); margin: 0;" x-text="fmtSize(details.file?.size || 0)"></dd></div>
                <div style="display: flex; justify-content: space-between; gap: .75rem;"><dt style="color: var(--ia-muted-dim);">{{ __('eys.file_manager.modified') }}</dt><dd style="color: var(--ia-cream); margin: 0;" x-text="fmtDate(details.file?.modified)"></dd></div>
                <div style="display: flex; justify-content: space-between; gap: .75rem; min-width: 0;"><dt style="color: var(--ia-muted-dim); flex-shrink: 0;">{{ __('eys.file_manager.url') }}</dt><dd style="color: var(--ia-copper-bright); margin: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" x-text="details.file?.url"></dd></div>
            </dl>
            <div class="fm-dialog-actions" style="justify-content: flex-start;">
                <button type="button" @click="copyUrl(details.file)" class="ia-btn ia-btn-secondary ip-btn-sm">{{ __('eys.file_manager.copy_url') }}</button>
                <button type="button" @click="download(details.file)" class="ia-btn ip-btn-sm">{{ __('eys.file_manager.download') }}</button>
            </div>
        </div>
    </div>

    {{-- Toast bildirimleri --}}
    <div class="fm-toasts">
        <template x-for="t in toasts" :key="t.id">
            <div class="fm-toast" :class="t.type === 'error' ? 'is-error' : (t.type === 'info' ? 'is-info' : '')" x-text="t.msg"></div>
        </template>
    </div>
</div>
