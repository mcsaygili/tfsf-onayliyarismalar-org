<section class="je-tags" aria-labelledby="jury-tags-title">
    <h2 id="jury-tags-title">{{ __('juri.tags.title') }}</h2>
    <p>{{ __('juri.tags.private_hint') }}</p>
    <noscript><p>{{ __('juri.tags.javascript_hint') }}</p></noscript>
    <form class="je-tag-create" @submit.prevent="create" x-cloak>
        <label for="jury-tag-name">{{ __('juri.tags.name') }}
            <input id="jury-tag-name" class="ia-input" type="text" x-model="name" maxlength="100" required :disabled="busy" autocomplete="off">
        </label>
        <label for="jury-tag-color">{{ __('juri.tags.color') }}
            <input id="jury-tag-color" type="color" x-model="color" :disabled="busy">
        </label>
        <button type="submit" class="ia-btn ia-btn-secondary" :disabled="busy || !name.trim()">{{ __('juri.tags.create') }}</button>
    </form>
    <p role="status" aria-live="polite" x-text="message" x-show="message" x-cloak></p>
    <p role="alert" class="je-tag-error" x-text="error" x-show="error" x-cloak></p>
    <div class="je-tag-filters" aria-label="{{ __('juri.tags.filter') }}" x-cloak>
        <button type="button" class="je-tag" :aria-pressed="selected === ''" @click="filter('')">{{ __('juri.tags.all') }} <span x-text="photoIds.length"></span></button>
        <template x-for="tag in tags" :key="tag.id">
            <div class="je-tag-filter">
                <button type="button" class="je-tag" :aria-pressed="selected === tag.id" @click="filter(tag.id)">
                    <span class="je-tag-dot" :style="{ backgroundColor: tag.color }" aria-hidden="true"></span><span x-text="tag.name"></span><span x-text="tag.photo_ids.length"></span>
                </button>
                <button type="button" class="je-tag-delete" :disabled="busy" @click="remove(tag)" :aria-label="@js(__('juri.tags.delete')) + ': ' + tag.name">{{ __('juri.tags.delete') }}</button>
            </div>
        </template>
    </div>
    <p x-show="tags.length === 0" x-cloak>{{ __('juri.tags.empty') }}</p>
</section>
