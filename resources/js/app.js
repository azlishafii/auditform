import Alpine from 'alpinejs';

window.Alpine = Alpine;

/* ---------- Lightbox (zoom imej + galeri) ---------- */
Alpine.data('lightbox', () => ({
    open: false,
    items: [],
    index: 0,
    title: '',
    show(detail) {
        this.items = detail.items || [];
        this.index = detail.index || 0;
        this.title = detail.title || '';
        this.open = this.items.length > 0;
        document.body.classList.toggle('overflow-hidden', this.open);
    },
    close() {
        this.open = false;
        document.body.classList.remove('overflow-hidden');
    },
    next() { this.index = (this.index + 1) % this.items.length; },
    prev() { this.index = (this.index - 1 + this.items.length) % this.items.length; },
    get current() { return this.items[this.index] || {}; },
}));

/* ---------- Tukar PDF kepada imej (dalam pelayar, tanpa keperluan server) ---------- */
// pdf.js dimuatkan hanya apabila admin import PDF, supaya borang awam kekal ringan.
// Worker dibina oleh Vite sebagai fail .js (bukan .mjs) kerana sesetengah server (cth: nginx
// FlashPanel) menghantar .mjs sebagai application/octet-stream dan pelayar enggan menjalankannya.
let pdfWorker = null;
async function pdfToImages(file, onProgress) {
    const [pdfjs, { default: PdfWorker }] = await Promise.all([
        import('pdfjs-dist'),
        import('pdfjs-dist/build/pdf.worker.min.mjs?worker'),
    ]);
    pdfWorker ??= new PdfWorker();
    pdfjs.GlobalWorkerOptions.workerPort = pdfWorker;

    const pdf = await pdfjs.getDocument({ data: await file.arrayBuffer() }).promise;
    const base = file.name.replace(/\.pdf$/i, '').replace(/[^\w-]+/g, '-').slice(0, 40) || 'halaman';
    const images = [];

    for (let n = 1; n <= pdf.numPages; n++) {
        onProgress?.(n, pdf.numPages);
        const page = await pdf.getPage(n);
        const unscaled = page.getViewport({ scale: 1 });
        // Sisi terpanjang ~2000px: cukup tajam untuk zoom, saiz fail kekal kecil.
        const scale = Math.min(4, 2000 / Math.max(unscaled.width, unscaled.height));
        const viewport = page.getViewport({ scale });

        const canvas = document.createElement('canvas');
        canvas.width = Math.round(viewport.width);
        canvas.height = Math.round(viewport.height);
        const ctx = canvas.getContext('2d');
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        await page.render({ canvasContext: ctx, viewport }).promise;

        const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.85));
        images.push(new File([blob], `${base}-${n}.jpg`, { type: 'image/jpeg' }));
        page.cleanup();
    }
    await pdf.destroy();
    return images;
}

/* ---------- Pembina borang (admin) ---------- */
const uid = () => Math.random().toString(36).slice(2, 10).padEnd(8, 'x');

Alpine.data('formBuilder', (initial, endpoints) => ({
    form: initial.form,
    fields: initial.fields.map((f) => ({ ...f, settings: { ...(f.settings || {}) }, _open: false, _key: uid() })),
    saving: false,
    errors: [],
    types: endpoints.types,
    uploading: 0,

    init() {
        window.addEventListener('beforeunload', (e) => {
            if (this.dirty && !this.saving) { e.preventDefault(); e.returnValue = ''; }
        });
        this.$watch('form', () => (this.dirty = true), { deep: true });
        this.$watch('fields', () => (this.dirty = true), { deep: true });
        this.$nextTick(() => (this.dirty = false));
    },
    dirty: false,

    hasOptions(t) { return ['single_choice', 'multi_choice', 'dropdown', 'image_choice', 'part_feedback'].includes(t); },
    hasImages(t) { return ['image_choice', 'part_feedback'].includes(t); },

    // Pilihan daripada soalan pilihan SEBELUM soalan ke-i (untuk soalan bersyarat)
    conditionOptions(i) {
        const out = [];
        this.fields.slice(0, i).forEach((f) => {
            if (!['single_choice', 'dropdown', 'image_choice', 'multi_choice'].includes(f.type)) return;
            f.options.forEach((o) => {
                if (o.label) out.push({ value: o.value, label: `${(f.label || 'Soalan').slice(0, 50)} → ${o.label}` });
            });
        });
        return out;
    },

    addField(type) {
        const f = { id: null, type, label: '', help: '', required: false, options: [], settings: {}, _open: true, _key: uid() };
        if (this.hasOptions(type)) {
            f.options = type === 'part_feedback' || type === 'image_choice'
                ? [this.blankOption(), this.blankOption()]
                : [this.blankOption('Pilihan 1'), this.blankOption('Pilihan 2')];
        }
        if (type === 'rating') f.settings.max = 5;
        if (type === 'image_choice') f.settings.allow_comment = true;
        this.fields.push(f);
        this.$nextTick(() => {
            const els = this.$root.querySelectorAll('[data-field]');
            els[els.length - 1]?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    },
    blankOption(label = '') { return { value: uid(), label, description: '', image: '', gallery: [] }; },
    removeField(i) {
        if (confirm('Buang soalan ini? Jawapan sedia ada bagi soalan ini akan turut dipadam apabila anda simpan.')) this.fields.splice(i, 1);
    },
    move(i, d) {
        const j = i + d;
        if (j < 0 || j >= this.fields.length) return;
        [this.fields[i], this.fields[j]] = [this.fields[j], this.fields[i]];
    },
    moveOption(f, i, d) {
        const j = i + d;
        if (j < 0 || j >= f.options.length) return;
        [f.options[i], f.options[j]] = [f.options[j], f.options[i]];
    },
    addOption(f) { f.options.push(this.blankOption()); },

    async upload(file) {
        this.uploading++;
        try {
            const fd = new FormData();
            fd.append('image', file);
            const res = await fetch(endpoints.upload, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': endpoints.csrf, Accept: 'application/json' },
                body: fd,
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.errors?.image?.[0] || data.message || 'Muat naik gagal');
            return data.url;
        } catch (e) {
            alert(e.message);
            return null;
        } finally {
            this.uploading--;
        }
    },
    async pickImage(opt, e) {
        const file = e.target.files[0];
        e.target.value = '';
        if (!file) return;
        const url = await this.upload(file);
        if (url) opt.image = url;
    },
    async addGallery(opt, e) {
        const files = [...e.target.files];
        e.target.value = '';
        for (const file of files) {
            const url = await this.upload(file);
            if (url) opt.gallery.push({ src: url, caption: file.name.replace(/\.[^.]+$/, '') });
        }
    },

    pdfStatus: '',
    async readPdf(e) {
        const file = e.target.files[0];
        e.target.value = '';
        if (!file) return null;
        if (file.type !== 'application/pdf' && !/\.pdf$/i.test(file.name)) { alert('Sila pilih fail PDF.'); return null; }
        this.uploading++;
        try {
            const pages = await pdfToImages(file, (n, total) => (this.pdfStatus = `Menukar halaman ${n}/${total}…`));
            const urls = [];
            for (const [i, page] of pages.entries()) {
                this.pdfStatus = `Memuat naik halaman ${i + 1}/${pages.length}…`;
                urls.push(await this.upload(page));
            }
            return urls;
        } catch (err) {
            alert('PDF tidak dapat dibaca: ' + err.message);
            return null;
        } finally {
            this.pdfStatus = '';
            this.uploading--;
        }
    },
    // Pilih imej: setiap halaman PDF menjadi imej galeri reka bentuk ini.
    async importPdfToGallery(opt, e) {
        const replace = opt.gallery.length > 0
            && confirm('Galeri ini sudah ada imej. Klik OK untuk GANTI galeri dengan halaman PDF, atau Cancel untuk TAMBAH di hujung.');
        const urls = await this.readPdf(e);
        if (!urls) return;
        const items = urls.map((src, i) => ({ src, caption: `Halaman ${i + 1}` })).filter((g) => g.src);
        opt.gallery = replace ? items : [...opt.gallery, ...items];
        if (!opt.image && items.length) opt.image = items[0].src;
    },
    // Halaman PDF dipadankan ikut susunan pilihan (halaman 1 → pilihan 1, …); halaman lebihan menjadi pilihan baharu.
    async importPdfToOptions(f, e) {
        const urls = await this.readPdf(e);
        if (!urls) return;
        const noun = f.type === 'image_choice' ? 'Draf' : 'Halaman';
        let added = 0;
        urls.forEach((src, i) => {
            if (!src) return;
            if (!f.options[i]) { f.options.push(this.blankOption(`${noun} ${i + 1}`)); added++; }
            f.options[i].image = src;
        });
        const unit = f.type === 'image_choice' ? 'reka bentuk' : 'bahagian';
        const notes = [`${urls.length} halaman diimport.`];
        if (added) notes.push(`${added} ${unit} baharu ditambah – sila semak nama dan penerangannya.`);
        if (urls.length < f.options.length) notes.push(`Soalan ini ada ${f.options.length} ${unit}; ${f.options.length - urls.length} ${unit} terakhir mengekalkan imej asal.`);
        alert(notes.join('\n'));
    },
    setCover(opt, src) { opt.image = src; },

    async save() {
        this.errors = [];
        if (!this.form.title.trim()) { this.errors = ['Sila isi tajuk borang.']; window.scrollTo({ top: 0, behavior: 'smooth' }); return; }
        this.saving = true;
        try {
            const body = {
                form: this.form,
                fields: this.fields.map(({ _open, _key, ...f }) => f),
            };
            const res = await fetch(endpoints.save, {
                method: endpoints.method,
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': endpoints.csrf },
                body: JSON.stringify(body),
            });
            const data = await res.json();
            if (res.status === 422) {
                this.errors = [...new Set(Object.values(data.errors || {}).flat())];
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } else if (!res.ok) {
                throw new Error(data.message || 'Ralat pelayan');
            } else {
                this.dirty = false;
                window.location = data.redirect;
                return;
            }
        } catch (e) {
            this.errors = [e.message];
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        this.saving = false;
    },
}));

/* ---------- Borang awam: maklum balas bahagian ---------- */
Alpine.data('partRow', (initialStatus = '') => ({
    status: initialStatus,
}));

window.copyText = async (text, btn) => {
    try {
        await navigator.clipboard.writeText(text);
    } catch {
        const t = document.createElement('textarea');
        t.value = text; document.body.appendChild(t); t.select(); document.execCommand('copy'); t.remove();
    }
    if (btn) { const o = btn.innerText; btn.innerText = 'Disalin ✓'; setTimeout(() => (btn.innerText = o), 1600); }
};

Alpine.start();
