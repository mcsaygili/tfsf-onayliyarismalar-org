/**
 * EYS Dosya Yöneticisi — Alpine bileşeni.
 * window.FM = { browse, thumb, download, upload, folder, rename, delete, bulkDelete, move, csrf, lang }.
 */
document.addEventListener('alpine:init', () => {
    window.Alpine.data('eysFileBrowser', (cfg = {}) => ({
        context: cfg.context || 'common',
        filetype: cfg.filetype || '',
        path: '',
        folders: [],
        files: [],
        loading: false,
        q: '',
        recursive: false,
        view: 'grid',
        page: 1,
        perPage: 60,
        total: 0,
        selected: [],
        clipboard: [],
        upload: { active: false, pct: 0, label: '' },
        lightbox: { open: false, url: '', name: '' },
        details: { open: false, file: null },
        dialog: { open: false, mode: 'folder', name: '', target: null },
        confirmBox: { open: false, msg: '', fn: null },
        toasts: [],
        dragover: false,

        init() {
            this.load(this.lastPath(this.context));
        },

        setContext(ctx) {
            this.context = ctx;
            this.q = '';
            this.recursive = false;
            this.selected = [];
            this.clipboard = [];
            this.load(this.lastPath(ctx));
        },

        lastPath(ctx) {
            try {
                return localStorage.getItem('fm:lastpath:' + ctx) || '';
            } catch (e) {
                return '';
            }
        },
        saveLastPath() {
            try {
                localStorage.setItem('fm:lastpath:' + this.context, this.path);
            } catch (e) {}
        },

        async load(path, page = 1) {
            this.loading = true;
            this.selected = [];
            try {
                const u = new URL(window.FM.browse, location.origin);
                u.searchParams.set('context', this.context);
                u.searchParams.set('path', path || '');
                u.searchParams.set('page', page);
                if (this.filetype) u.searchParams.set('filetype', this.filetype);
                if (this.q) u.searchParams.set('q', this.q);
                if (this.recursive) u.searchParams.set('recursive', '1');
                const r = await fetch(u, { headers: { Accept: 'application/json' } });
                const d = await r.json();
                this.path = d.path || '';
                this.saveLastPath();
                this.folders = d.folders || [];
                this.files = d.files || [];
                this.page = d.page || 1;
                this.perPage = d.per_page || 60;
                this.total = d.total || 0;
            } catch (e) {
                console.error('FM load', e);
            }
            this.loading = false;
        },

        runSearch() {
            this.load(this.path, 1);
        },
        toggleRecursive() {
            this.recursive = !this.recursive;
            this.load(this.path, 1);
        },
        up() {
            const p = this.path.includes('/') ? this.path.slice(0, this.path.lastIndexOf('/')) : '';
            this.load(p);
        },

        get pageCount() {
            return Math.max(1, Math.ceil(this.total / this.perPage));
        },
        goPage(p) {
            if (p >= 1 && p <= this.pageCount) this.load(this.path, p);
        },

        get crumbs() {
            const out = [{ name: window.FM.lang.home, path: '' }];
            if (this.path) {
                let acc = '';
                this.path.split('/').forEach((seg) => {
                    acc = acc ? acc + '/' + seg : seg;
                    out.push({ name: seg, path: acc });
                });
            }
            return out;
        },

        isSelected(p) {
            return this.selected.includes(p);
        },
        toggleSelect(item) {
            const i = this.selected.indexOf(item.path);
            if (i === -1) this.selected.push(item.path);
            else this.selected.splice(i, 1);
        },
        selectAllVisible() {
            const all = [...this.folders.map((f) => f.path), ...this.files.map((f) => f.path)];
            this.selected = this.selected.length === all.length ? [] : all;
        },

        async post(url, fd) {
            fd.append('_token', window.FM.csrf);
            fd.append('context', this.context);
            const r = await fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': window.FM.csrf, Accept: 'application/json' }, body: fd });
            return r.ok ? r.json() : Promise.reject(await r.json().catch(() => ({})));
        },

        uploadFiles(ev) {
            this.uploadList(Array.from(ev.target.files || []));
            ev.target.value = '';
        },
        onDrop(ev) {
            this.dragover = false;
            const f = ev.dataTransfer && ev.dataTransfer.files;
            if (f && f.length) this.uploadList(Array.from(f));
        },
        uploadList(arr) {
            if (!arr.length) return;
            const fd = new FormData();
            fd.append('_token', window.FM.csrf);
            fd.append('context', this.context);
            fd.append('path', this.path);
            if (this.filetype) fd.append('filetype', this.filetype);
            for (const f of arr) fd.append('files[]', f);
            const xhr = new XMLHttpRequest();
            xhr.open('POST', window.FM.upload);
            xhr.setRequestHeader('X-CSRF-TOKEN', window.FM.csrf);
            xhr.setRequestHeader('Accept', 'application/json');
            this.upload = { active: true, pct: 0, label: arr.length + ' ' + window.FM.lang.files };
            this._guard = (e) => {
                e.preventDefault();
                e.returnValue = '';
            };
            window.addEventListener('beforeunload', this._guard);
            const done = () => {
                this.upload.active = false;
                window.removeEventListener('beforeunload', this._guard);
            };
            xhr.upload.onprogress = (e) => {
                if (e.lengthComputable) this.upload.pct = Math.round((e.loaded / e.total) * 100);
            };
            xhr.onload = () => {
                done();
                let d = {};
                try {
                    d = JSON.parse(xhr.responseText);
                } catch (e) {}
                if (xhr.status >= 200 && xhr.status < 300) {
                    if (d.saved) this.toast('success', d.saved + ' ' + window.FM.lang.uploaded);
                    (d.errors || []).forEach((er) => this.toast('error', er));
                } else {
                    this.toast('error', d.message || window.FM.lang.error);
                }
                this.load(this.path, this.page);
            };
            xhr.onerror = () => {
                done();
                this.toast('error', window.FM.lang.error);
            };
            xhr.send(fd);
        },

        openFolderDialog() {
            this.dialog = { open: true, mode: 'folder', name: '', target: null };
            this.$nextTick(() => this.$refs.dialogInput && this.$refs.dialogInput.focus());
        },
        openRenameDialog(item) {
            this.dialog = { open: true, mode: 'rename', name: item.name, target: item };
            this.$nextTick(() => this.$refs.dialogInput && this.$refs.dialogInput.focus());
        },
        async submitDialog() {
            const name = (this.dialog.name || '').trim();
            if (!name) return;
            const fd = new FormData();
            try {
                if (this.dialog.mode === 'folder') {
                    fd.append('path', this.path);
                    fd.append('name', name);
                    await this.post(window.FM.folder, fd);
                } else {
                    fd.append('path', this.dialog.target.path);
                    fd.append('name', name);
                    await this.post(window.FM.rename, fd);
                }
                this.dialog.open = false;
                this.load(this.path, this.page);
            } catch (e) {
                this.toast('error', (e && e.message) || window.FM.lang.error);
            }
        },

        askConfirm(msg, fn) {
            this.confirmBox = { open: true, msg, fn };
        },
        async confirmYes() {
            const fn = this.confirmBox.fn;
            this.confirmBox.open = false;
            if (fn) await fn();
        },
        removeItem(item) {
            this.askConfirm(window.FM.lang.confirm_delete.replace(':name', item.name), async () => {
                const fd = new FormData();
                fd.append('path', item.path);
                try {
                    await this.post(window.FM.delete, fd);
                    this.toast('success', window.FM.lang.deleted);
                    this.load(this.path, this.page);
                } catch (e) {
                    this.toast('error', (e && e.message) || window.FM.lang.error);
                }
            });
        },
        bulkDelete() {
            if (!this.selected.length) return;
            this.askConfirm(window.FM.lang.confirm_delete_n.replace(':count', this.selected.length), async () => {
                const fd = new FormData();
                this.selected.forEach((p) => fd.append('paths[]', p));
                try {
                    const d = await this.post(window.FM.bulkDelete, fd);
                    this.toast('success', (d.deleted || 0) + ' ' + window.FM.lang.deleted);
                    this.load(this.path, this.page);
                } catch (e) {
                    this.toast('error', (e && e.message) || window.FM.lang.error);
                }
            });
        },

        cut() {
            if (!this.selected.length) return;
            this.clipboard = [...this.selected];
            this.toast('info', this.clipboard.length + ' ' + window.FM.lang.cut_done);
            this.selected = [];
        },
        async pasteHere() {
            if (!this.clipboard.length) return;
            const fd = new FormData();
            this.clipboard.forEach((p) => fd.append('paths[]', p));
            fd.append('target', this.path);
            try {
                const d = await this.post(window.FM.move, fd);
                this.toast('success', (d.moved || 0) + ' ' + window.FM.lang.moved);
                this.clipboard = [];
                this.load(this.path, this.page);
            } catch (e) {
                this.toast('error', (e && e.message) || window.FM.lang.error);
            }
        },

        openLightbox(file) {
            this.lightbox = { open: true, url: file.url, name: file.name };
        },
        openDetails(file) {
            this.details = { open: true, file };
        },
        download(file) {
            window.location = window.FM.download + '?context=' + encodeURIComponent(this.context) + '&path=' + encodeURIComponent(file.path);
        },
        copyUrl(file) {
            if (navigator.clipboard) navigator.clipboard.writeText(file.url);
            this.toast('success', window.FM.lang.copied);
        },

        toast(type, msg) {
            const id = Date.now() + Math.random();
            this.toasts.push({ id, type, msg });
            setTimeout(() => {
                this.toasts = this.toasts.filter((t) => t.id !== id);
            }, 3500);
        },

        fmtSize(b) {
            if (b >= 1048576) return (b / 1048576).toFixed(1) + ' MB';
            if (b >= 1024) return (b / 1024).toFixed(0) + ' KB';
            return b + ' B';
        },
        fmtDate(ts) {
            try {
                return new Date(ts * 1000).toLocaleString();
            } catch (e) {
                return '';
            }
        },
    }));
});
