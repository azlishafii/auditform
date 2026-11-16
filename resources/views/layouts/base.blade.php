<!DOCTYPE html>
<html lang="ms" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    @if (! empty($noindex ?? true))
        <meta name="robots" content="noindex, nofollow">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-slate-50 font-sans text-slate-800 antialiased">
    @yield('body')

    {{-- Lightbox global: zoom imej & galeri --}}
    <div x-data="lightbox" x-cloak
         @open-lightbox.window="show($event.detail)"
         @keydown.escape.window="close()"
         @keydown.arrow-right.window="open && next()"
         @keydown.arrow-left.window="open && prev()"
         x-show="open" x-transition.opacity
         class="fixed inset-0 z-[100] flex flex-col bg-slate-950/95">
        <div class="flex items-center justify-between gap-4 px-4 py-3 text-white">
            <div class="min-w-0">
                <p class="truncate text-sm font-semibold" x-text="title"></p>
                <p class="truncate text-xs text-slate-300"><span x-text="current.caption"></span> <span x-show="items.length > 1" class="text-slate-400">· <span x-text="index + 1"></span>/<span x-text="items.length"></span></span></p>
            </div>
            <button type="button" @click="close()" class="rounded-lg bg-white/10 px-3 py-1.5 text-sm font-medium hover:bg-white/20">Tutup ✕</button>
        </div>
        <div class="relative flex min-h-0 flex-1 items-center justify-center px-2 pb-2" @click.self="close()">
            <button type="button" x-show="items.length > 1" @click="prev()" class="absolute left-2 z-10 rounded-full bg-white/15 p-3 text-white hover:bg-white/30" aria-label="Sebelum">
                <svg class="size-5" viewBox="0 0 20 20" fill="currentColor"><path d="M12.7 5.3a1 1 0 0 1 0 1.4L9.4 10l3.3 3.3a1 1 0 1 1-1.4 1.4l-4-4a1 1 0 0 1 0-1.4l4-4a1 1 0 0 1 1.4 0Z"/></svg>
            </button>
            <img :src="current.src" :alt="current.caption" class="max-h-full max-w-full rounded-lg object-contain shadow-2xl">
            <button type="button" x-show="items.length > 1" @click="next()" class="absolute right-2 z-10 rounded-full bg-white/15 p-3 text-white hover:bg-white/30" aria-label="Seterusnya">
                <svg class="size-5" viewBox="0 0 20 20" fill="currentColor"><path d="M7.3 14.7a1 1 0 0 1 0-1.4l3.3-3.3-3.3-3.3a1 1 0 0 1 1.4-1.4l4 4a1 1 0 0 1 0 1.4l-4 4a1 1 0 0 1-1.4 0Z"/></svg>
            </button>
        </div>
        <div x-show="items.length > 1" class="flex gap-2 overflow-x-auto px-4 pb-4">
            <template x-for="(it, i) in items" :key="i">
                <button type="button" @click="index = i" class="shrink-0 overflow-hidden rounded-md ring-2 transition" :class="i === index ? 'ring-brand-400' : 'ring-transparent opacity-60 hover:opacity-100'">
                    <img :src="it.src" :alt="it.caption" class="h-16 w-12 object-cover">
                </button>
            </template>
        </div>
    </div>
</body>
</html>
