<x-marketing-layout :title="$page->title">
    <section class="relative isolate overflow-hidden border-b border-slate-200 bg-gradient-to-br from-brand-50 via-white to-sky-50">
        <div class="bg-dots-dark absolute inset-0 -z-10"></div>
        <div class="mx-auto max-w-3xl px-4 py-16 text-center sm:px-6 lg:px-8 lg:py-20">
            <h1 class="font-display text-4xl font-bold tracking-tight text-slate-900 sm:text-5xl">{{ $page->title }}</h1>
            @if ($page->subtitle)
                <p class="mx-auto mt-4 max-w-2xl text-lg text-slate-600">{{ $page->subtitle }}</p>
            @endif
        </div>
    </section>

    <article class="mx-auto max-w-3xl px-4 py-16 sm:px-6 lg:px-8">
        <div class="rich-text text-[17px] leading-relaxed text-slate-700">{!! $page->content !!}</div>
    </article>

    <section class="mx-auto mb-20 max-w-3xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col items-center justify-between gap-4 rounded-2xl bg-slate-50 p-8 text-center ring-1 ring-slate-200 sm:flex-row sm:text-left">
            <div>
                <p class="text-lg font-semibold text-slate-900">Bring learning, testing and reading together</p>
                <p class="mt-1 text-sm text-slate-600">Create a workspace for your organization in minutes.</p>
            </div>
            <x-button :href="route('signup')" variant="dark" icon-right="arrow-right">Get started</x-button>
        </div>
    </section>
</x-marketing-layout>
