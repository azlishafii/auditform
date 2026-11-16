@extends('layouts.admin')
@section('title', ($form->exists ? 'Sunting' : 'Borang baharu').' – '.config('app.name'))

@section('content')
@php
    $endpoints = [
        'save' => $form->exists ? route('admin.forms.update', $form) : route('admin.forms.store'),
        'method' => $form->exists ? 'PUT' : 'POST',
        'upload' => route('admin.upload'),
        'csrf' => csrf_token(),
        'types' => \App\Models\FormField::TYPES,
    ];
@endphp

<div x-data="formBuilder(@js($payload), @js($endpoints))" class="pb-28">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <a href="{{ $form->exists ? route('admin.forms.results', $form) : route('admin.forms.index') }}" class="text-sm text-slate-500 hover:text-slate-800">← Kembali</a>
            <h1 class="text-2xl font-bold text-slate-900">{{ $form->exists ? 'Sunting borang' : 'Borang baharu' }}</h1>
        </div>
    </div>

    @if ($responseCount > 0)
        <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Borang ini sudah mempunyai <strong>{{ $responseCount }}</strong> maklum balas. Mengubah teks soalan/pilihan adalah selamat, tetapi <strong>membuang soalan</strong> akan memadam jawapan bagi soalan itu.
        </div>
    @endif

    <template x-if="errors.length">
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <ul class="list-disc pl-5"><template x-for="e in errors"><li x-text="e"></li></template></ul>
        </div>
    </template>

    {{-- Tetapan asas --}}
    <section class="card mb-6 space-y-5 p-6">
        <div>
            <label class="label">Tajuk borang <span class="text-red-500">*</span></label>
            <input type="text" x-model="form.title" class="input text-base font-semibold" placeholder="cth: Pemilihan Draf Konsep Buku Laporan Tahunan">
        </div>
        <div>
            <label class="label">Penerangan / arahan kepada responden</label>
            <textarea x-model="form.description" rows="3" class="input" placeholder="Terangkan tujuan borang ini..."></textarea>
        </div>
        <div class="grid gap-5 md:grid-cols-2">
            <div>
                <label class="label">Status</label>
                <select x-model="form.status" class="input">
                    <option value="draft">Draf (hanya admin boleh pratonton)</option>
                    <option value="open">Dibuka (responden boleh menjawab)</option>
                    <option value="closed">Ditutup</option>
                </select>
            </div>
            <div>
                <label class="label">Tarikh &amp; masa tutup (pilihan)</label>
                <input type="datetime-local" x-model="form.closes_at" class="input">
            </div>
        </div>
        <div class="grid gap-3 md:grid-cols-2">
            <label class="flex items-start gap-3 rounded-xl border border-slate-200 p-3.5 text-sm">
                <input type="checkbox" x-model="form.collect_identity" class="mt-0.5 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                <span><strong class="block text-slate-800">Minta nama responden</strong><span class="text-slate-500">Papar ruang nama di bahagian atas borang. Ruang Jawatan/Unit hanya muncul jika senarai nama di bawah dikosongkan.</span></span>
            </label>
            <label class="flex items-start gap-3 rounded-xl border border-slate-200 p-3.5 text-sm" :class="!form.collect_identity && 'opacity-50'">
                <input type="checkbox" x-model="form.identity_required" :disabled="!form.collect_identity" class="mt-0.5 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                <span><strong class="block text-slate-800">Nama wajib diisi</strong><span class="text-slate-500">Nyahtanda untuk benarkan maklum balas tanpa nama.</span></span>
            </label>
            <div class="md:col-span-2" x-show="form.collect_identity">
                <label class="label">Senarai nama responden (pilihan)</label>
                <textarea x-model="form.respondent_options" rows="4" class="input" placeholder="Satu nama setiap baris. Jika diisi, responden hanya memilih nama daripada senarai (dropdown) dan ruang Jawatan/Unit disembunyikan."></textarea>
                <p class="mt-1 text-xs text-slate-500">Biarkan kosong jika mahu responden menaip nama sendiri.</p>
            </div>
            <label class="flex items-start gap-3 rounded-xl border border-slate-200 p-3.5 text-sm md:col-span-2">
                <input type="checkbox" x-model="form.allow_multiple" class="mt-0.5 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                <span><strong class="block text-slate-800">Benarkan hantaran berulang daripada peranti yang sama</strong><span class="text-slate-500">Jika tidak ditanda, satu peranti/pelayar hanya boleh menghantar sekali.</span></span>
            </label>
        </div>
        <div>
            <label class="label">Mesej selepas hantar</label>
            <textarea x-model="form.thank_you" rows="2" class="input" placeholder="Terima kasih atas maklum balas anda."></textarea>
        </div>
    </section>

    {{-- Soalan --}}
    <h2 class="mb-3 text-lg font-semibold text-slate-900">Soalan</h2>

    <div class="space-y-4">
        <template x-for="(f, i) in fields" :key="f._key">
            <div data-field class="card overflow-hidden">
                <div class="flex items-center gap-3 border-b border-slate-100 bg-slate-50 px-4 py-2.5">
                    <span class="grid size-6 place-items-center rounded-md bg-white text-xs font-bold text-slate-500 ring-1 ring-slate-200" x-text="i + 1"></span>
                    <span class="rounded-full bg-brand-100 px-2.5 py-0.5 text-xs font-semibold text-brand-800" x-text="types[f.type]"></span>
                    <span class="min-w-0 flex-1 truncate text-sm text-slate-500" x-text="f.label" x-show="!f._open"></span>
                    <div class="ml-auto flex items-center gap-1">
                        <button type="button" @click="move(i, -1)" :disabled="i === 0" class="rounded-md p-1.5 text-slate-500 hover:bg-white disabled:opacity-30" title="Naik">↑</button>
                        <button type="button" @click="move(i, 1)" :disabled="i === fields.length - 1" class="rounded-md p-1.5 text-slate-500 hover:bg-white disabled:opacity-30" title="Turun">↓</button>
                        <button type="button" @click="f._open = !f._open" class="rounded-md px-2 py-1.5 text-xs font-medium text-slate-600 hover:bg-white" x-text="f._open ? 'Lipat' : 'Buka'"></button>
                        <button type="button" @click="removeField(i)" class="rounded-md px-2 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50">Buang</button>
                    </div>
                </div>

                <div x-show="f._open" x-collapse class="space-y-4 p-5">
                    <div>
                        <label class="label" x-text="f.type === 'heading' ? 'Tajuk bahagian' : 'Soalan'"></label>
                        <input type="text" x-model="f.label" class="input" placeholder="Tulis soalan di sini...">
                    </div>
                    <div>
                        <label class="label">Penerangan / panduan (pilihan)</label>
                        <textarea x-model="f.help" rows="2" class="input"></textarea>
                    </div>

                    <label class="flex items-center gap-2 text-sm text-slate-700" x-show="f.type !== 'heading'">
                        <input type="checkbox" x-model="f.required" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500"> Wajib dijawab
                    </label>

                    {{-- Soalan bersyarat --}}
                    <div x-show="conditionOptions(i).length" class="rounded-xl bg-slate-50 p-4">
                        <label class="label">Papar soalan ini hanya jika responden memilih…</label>
                        <select x-model="f.settings.show_if" class="input">
                            <option value="">Sentiasa papar</option>
                            <template x-for="c in conditionOptions(i)" :key="c.value">
                                <option :value="c.value" :selected="c.value === f.settings.show_if" x-text="c.label"></option>
                            </template>
                        </select>
                    </div>

                    {{-- Tetapan penilaian --}}
                    <div x-show="f.type === 'rating'">
                        <label class="label">Skala maksimum</label>
                        <select x-model.number="f.settings.max" class="input w-32">
                            <template x-for="n in [3,4,5,6,7,8,9,10]"><option :value="n" x-text="n + ' bintang'"></option></template>
                        </select>
                    </div>

                    {{-- Tetapan pilih imej --}}
                    <div x-show="f.type === 'image_choice'" class="space-y-3 rounded-xl bg-slate-50 p-4">
                        <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" x-model="f.settings.allow_comment" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500"> Sediakan ruang komen/sebab selepas memilih
                        </label>
                        <input type="text" x-show="f.settings.allow_comment" x-model="f.settings.comment_label" class="input" placeholder="Label ruang komen, cth: Sebab anda memilih draf ini">
                    </div>

                    {{-- Pilihan --}}
                    <div x-show="hasOptions(f.type)">
                        <label class="label" x-text="f.type === 'part_feedback' ? 'Senarai bahagian untuk dinilai' : (hasImages(f.type) ? 'Pilihan / reka bentuk' : 'Pilihan jawapan')"></label>
                        <p class="mb-2 text-xs text-slate-500" x-show="f.type === 'part_feedback'">Setiap bahagian akan dipaparkan dengan butang (Suka / Boleh diterima / Perlu penambahbaikan) dan ruang komen. Imej adalah pilihan.</p>
                        <div class="space-y-3">
                            <template x-for="(o, oi) in f.options" :key="o.value">
                                <div class="rounded-xl border border-slate-200 p-3">
                                    <div class="flex items-start gap-3">
                                        <div class="min-w-0 flex-1 space-y-2">
                                            <input type="text" x-model="o.label" class="input" :placeholder="hasImages(f.type) ? 'Nama (cth: Draf 1)' : 'Pilihan ' + (oi + 1)">
                                            <input type="text" x-show="f.type === 'image_choice'" x-model="o.description" class="input" placeholder="Penerangan ringkas (pilihan)">
                                        </div>
                                        <div class="flex shrink-0 flex-col gap-1">
                                            <button type="button" @click="moveOption(f, oi, -1)" :disabled="oi === 0" class="rounded p-1 text-slate-400 hover:bg-slate-100 disabled:opacity-30">↑</button>
                                            <button type="button" @click="moveOption(f, oi, 1)" :disabled="oi === f.options.length - 1" class="rounded p-1 text-slate-400 hover:bg-slate-100 disabled:opacity-30">↓</button>
                                        </div>
                                        <button type="button" @click="f.options.splice(oi, 1)" class="rounded p-1 text-red-500 hover:bg-red-50" title="Buang pilihan">✕</button>
                                    </div>

                                    <div x-show="hasImages(f.type)" class="mt-3 space-y-3 border-t border-slate-100 pt-3">
                                        <div class="flex items-center gap-3">
                                            <template x-if="o.image"><img :src="o.image" class="h-20 w-14 rounded-md border border-slate-200 object-cover"></template>
                                            <label class="btn-ghost cursor-pointer text-xs">
                                                <span x-text="o.image ? 'Tukar imej' : 'Muat naik imej'"></span>
                                                <input type="file" accept="image/*" class="hidden" @change="pickImage(o, $event)">
                                            </label>
                                            <button type="button" x-show="o.image" @click="o.image = ''" class="text-xs text-red-600 hover:underline">Buang imej</button>
                                        </div>

                                        <div x-show="f.type === 'image_choice'">
                                            <p class="mb-1.5 text-xs font-medium text-slate-600">Galeri bahagian (pilihan) – responden boleh lihat &amp; zoom satu persatu</p>
                                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                                                <template x-for="(g, gi) in o.gallery" :key="g.src + gi">
                                                    <div class="rounded-lg border border-slate-200 p-1.5">
                                                        <img :src="g.src" class="mb-1.5 h-24 w-full rounded object-cover object-top">
                                                        <input type="text" x-model="g.caption" class="w-full rounded border border-slate-200 px-1.5 py-1 text-xs" placeholder="Kapsyen">
                                                        <div class="mt-1 flex items-center justify-between gap-2">
                                                            <button type="button" @click="o.gallery.splice(gi, 1)" class="text-xs text-red-600 hover:underline">Buang</button>
                                                            <button type="button" x-show="o.image !== g.src" @click="setCover(o, g.src)" class="text-xs text-brand-700 hover:underline">Jadikan imej utama</button>
                                                            <span x-show="o.image === g.src" class="text-xs font-medium text-emerald-700">Imej utama</span>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                            <div class="mt-2 flex flex-wrap gap-2">
                                                <label class="btn-ghost cursor-pointer text-xs">+ Tambah imej galeri
                                                    <input type="file" accept="image/*" multiple class="hidden" @change="addGallery(o, $event)">
                                                </label>
                                                <label class="btn-ghost cursor-pointer text-xs" :class="uploading && 'pointer-events-none opacity-50'">📄 Import PDF (setiap halaman jadi imej)
                                                    <input type="file" accept="application/pdf,.pdf" class="hidden" @change="importPdfToGallery(o, $event)">
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <button type="button" @click="addOption(f)" class="btn-ghost text-xs" x-text="f.type === 'part_feedback' ? '+ Tambah bahagian' : (f.type === 'image_choice' ? '+ Tambah reka bentuk' : '+ Tambah pilihan')"></button>
                            <label x-show="hasImages(f.type)" class="btn-ghost cursor-pointer text-xs" :class="uploading && 'pointer-events-none opacity-50'">
                                <span x-text="f.type === 'part_feedback' ? '📄 Import PDF ke bahagian' : '📄 Import PDF (satu halaman = satu reka bentuk)'"></span>
                                <input type="file" accept="application/pdf,.pdf" class="hidden" @change="importPdfToOptions(f, $event)">
                            </label>
                        </div>
                        <p x-show="hasImages(f.type)" class="mt-1.5 text-xs text-slate-500" x-text="f.type === 'part_feedback'
                            ? 'Halaman PDF dipadankan ikut susunan: halaman 1 → bahagian 1, halaman 2 → bahagian 2, dan seterusnya. Halaman lebihan menjadi bahagian baharu.'
                            : 'Halaman PDF menjadi imej utama ikut susunan: halaman 1 → reka bentuk 1, dan seterusnya. Halaman lebihan menjadi reka bentuk baharu. Untuk PDF satu buku penuh, guna butang Import PDF dalam galeri reka bentuk itu.'"></p>
                    </div>
                </div>
            </div>
        </template>

        <div x-show="!fields.length" class="rounded-2xl border-2 border-dashed border-slate-300 px-6 py-10 text-center text-sm text-slate-500">
            Belum ada soalan. Tambah soalan pertama anda di bawah.
        </div>
    </div>

    {{-- Tambah soalan --}}
    <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-4">
        <p class="mb-3 text-sm font-semibold text-slate-700">+ Tambah soalan</p>
        <div class="flex flex-wrap gap-2">
            <template x-for="(label, type) in types" :key="type">
                <button type="button" @click="addField(type)" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 transition hover:border-brand-500 hover:bg-brand-50 hover:text-brand-800" x-text="label"></button>
            </template>
        </div>
    </div>

    {{-- Bar simpan --}}
    <div class="fixed inset-x-0 bottom-0 z-30 border-t border-slate-200 bg-white/95 backdrop-blur">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-3">
            <p class="text-sm text-slate-500"><span x-show="uploading" class="font-medium text-brand-700" x-text="pdfStatus || 'Memuat naik imej…'"></span><span x-show="!uploading && dirty">Ada perubahan belum disimpan</span></p>
            <div class="flex gap-2">
                <a href="{{ $form->exists ? route('admin.forms.results', $form) : route('admin.forms.index') }}" class="btn-ghost">Batal</a>
                <button type="button" @click="save()" :disabled="saving || uploading > 0" class="btn-primary" x-text="saving ? 'Menyimpan…' : 'Simpan borang'"></button>
            </div>
        </div>
    </div>
</div>
@endsection
