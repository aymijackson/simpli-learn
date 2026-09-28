<x-app-layout :title="$file->title">
    <a href="{{ route('library.resources.show', $resource) }}" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-700">
        &larr; {{ $resource->title }}
    </a>

    <div
        data-epub-reader
        data-load-error="{{ __('This EPUB could not be loaded.') }}"
        data-stream-url="{{ route('library.resources.files.stream', [$resource, $file]) }}"
        data-progress-url="{{ route('library.resources.files.progress', [$resource, $file]) }}"
        data-csrf-token="{{ csrf_token() }}"
        @if ($initialPosition) data-initial-location="{{ $initialPosition }}" @endif
    >
        <div class="mb-4 flex items-center justify-between">
            <button type="button" data-epub-prev class="rounded-lg bg-white px-3 py-1.5 text-sm font-medium text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">{{ __('← Prev') }}</button>
            <button type="button" data-epub-next class="rounded-lg bg-white px-3 py-1.5 text-sm font-medium text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">{{ __('Next →') }}</button>
        </div>
        <div data-epub-viewer class="rounded-lg bg-white ring-1 ring-slate-200"></div>
    </div>

    @push('scripts')
        <script src="{{ asset('vendor/jszip-3.10.1/jszip.min.js') }}"></script>
        <script src="{{ asset('vendor/epubjs-0.3.93/epub.min.js') }}"></script>
        <script src="{{ asset('js/library-epub-reader.js') }}?v={{ filemtime(public_path('js/library-epub-reader.js')) }}"></script>
    @endpush
</x-app-layout>
