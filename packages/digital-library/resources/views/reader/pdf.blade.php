<x-app-layout :title="$file->title">
    <a href="{{ route('library.resources.show', $resource) }}" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-700">
        &larr; {{ $resource->title }}
    </a>

    <div
        data-pdf-reader
        data-stream-url="{{ route('library.resources.files.stream', [$resource, $file]) }}"
        data-progress-url="{{ route('library.resources.files.progress', [$resource, $file]) }}"
        data-csrf-token="{{ csrf_token() }}"
        data-initial-page="{{ $initialPosition ?? '1' }}"
    >
        <div class="mb-4 flex items-center justify-between">
            <button type="button" data-pdf-prev class="rounded-lg bg-white px-3 py-1.5 text-sm font-medium text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 disabled:opacity-50">&larr; Prev</button>
            <span data-pdf-page-indicator class="text-sm text-slate-500">Loading&hellip;</span>
            <button type="button" data-pdf-next class="rounded-lg bg-white px-3 py-1.5 text-sm font-medium text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 disabled:opacity-50">Next &rarr;</button>
        </div>
        <div class="flex justify-center overflow-auto rounded-lg bg-slate-100 p-4">
            <canvas data-pdf-canvas></canvas>
        </div>
    </div>

    @push('scripts')
        @vite(['resources/js/library-pdf-reader.js'])
    @endpush
</x-app-layout>
