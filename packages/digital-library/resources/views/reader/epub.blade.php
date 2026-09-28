@php
    // Secure files get the copy deterrents and a watermark with the viewer's name.
    $protected = $file->access_level->value === 'secure';
    $watermark = $protected ? auth()->user()->name.' · '.auth()->user()->email.' · '.now()->format('Y-m-d') : null;
@endphp
<x-app-layout :title="$file->title">
    <a href="{{ route('library.resources.show', $resource) }}" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-700">
        &larr; {{ $resource->title }}
    </a>

    <div
        data-epub-reader
        @if ($protected) class="print:hidden" @endif
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
        <div data-epub-viewer class="rounded-lg bg-white ring-1 ring-slate-200" @if ($protected) data-protected data-watermark="{{ $watermark }}" @endif></div>
    </div>

    @if ($protected)
        <p class="hidden rounded-lg bg-slate-100 p-6 text-center text-sm text-slate-600 print:block">{{ __('This document can only be viewed on the platform.') }}</p>
    @endif

    @push('scripts')
        @if ($protected)
            <script src="{{ \App\Support\Asset::url('js/library-protect.js') }}"></script>
        @endif
        <script src="{{ asset('vendor/jszip-3.10.1/jszip.min.js') }}"></script>
        <script src="{{ asset('vendor/epubjs-0.3.93/epub.min.js') }}"></script>
        <script src="{{ \App\Support\Asset::url('js/library-epub-reader.js') }}"></script>
    @endpush
</x-app-layout>
