export default function registerJuryTags(Alpine) {
    Alpine.data('juryTags', (config) => ({
        tags: config.tags,
        selected: config.selected,
        photoIds: config.photoIds,
        photoGroups: config.photoGroups || [],
        name: '',
        color: '#6576ff',
        busy: false,
        message: '',
        error: '',
        scoreDirty: false,
        unloadHandler: null,
        init() {
            this.unloadHandler = (event) => {
                if (!this.scoreDirty) return;
                event.preventDefault();
                event.returnValue = '';
            };
            window.addEventListener('beforeunload', this.unloadHandler);
        },
        destroy() { window.removeEventListener('beforeunload', this.unloadHandler); },
        has(tag, photoId) { return tag.photo_ids.includes(photoId); },
        visible(photoId) {
            const group = this.photoGroups.find((ids) => ids.includes(photoId)) || [photoId];
            return !this.selected || this.tags.some((tag) => tag.id === this.selected && group.some((id) => this.has(tag, id)));
        },
        get visibleCount() { return this.photoIds.filter((id) => this.visible(id)).length; },
        filter(id) {
            this.selected = id;
            const url = new URL(window.location.href);
            if (id) url.searchParams.set('tag', id);
            else url.searchParams.delete('tag');
            window.history.replaceState({}, '', url);
        },
        merge(tag) {
            const index = this.tags.findIndex((item) => item.id === tag.id);
            if (index === -1) this.tags.push(tag);
            else this.tags.splice(index, 1, tag);
        },
        async send(path, method, body) {
            const response = await fetch(config.baseUrl + path, {
                method,
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: body ? JSON.stringify(body) : undefined,
            });
            if (!response.ok) {
                const data = await response.json().catch(() => ({}));
                const validation = Object.values(data.errors || {}).flat()[0];
                throw new Error(validation || config.errors[response.status] || config.errors.default);
            }
            return response.status === 204 ? null : response.json();
        },
        async perform(action, success) {
            if (this.busy) return;
            this.busy = true;
            this.error = '';
            this.message = '';
            try {
                await action();
                this.message = success;
            } catch (error) {
                this.error = error instanceof TypeError ? config.errors.network : error.message;
            } finally {
                this.busy = false;
            }
        },
        async create() {
            await this.perform(async () => {
                const data = await this.send('', 'POST', { name: this.name, color: this.color });
                this.merge(data.tag);
                this.name = '';
            }, config.messages.created);
        },
        async remove(tag) {
            if (this.busy || !window.confirm(config.messages.confirmDelete.replace(':name', tag.name))) return;
            await this.perform(async () => {
                await this.send('/' + tag.id, 'DELETE');
                this.tags = this.tags.filter((item) => item.id !== tag.id);
                if (this.selected === tag.id) this.filter('');
            }, config.messages.deleted);
        },
        async toggle(tag, photoId) {
            const detach = this.has(tag, photoId);
            await this.perform(async () => {
                const data = await this.send('/' + tag.id + '/fotograflar/' + photoId, detach ? 'DELETE' : 'PUT');
                this.merge(data.tag);
            }, detach ? config.messages.detached : config.messages.attached);
        },
    }));
}
