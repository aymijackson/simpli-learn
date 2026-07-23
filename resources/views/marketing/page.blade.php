<x-marketing-layout :title="$page->title">
    <section class="mx-auto max-w-3xl px-4 py-16 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold tracking-tight text-slate-900">{{ $page->title }}</h1>
        @if ($page->subtitle)
            <p class="mt-3 text-lg text-slate-600">{{ $page->subtitle }}</p>
        @endif
        <div class="rich-text mt-8 text-base text-slate-700">{!! $page->content !!}</div>
    </section>
</x-marketing-layout>
