{{-- Secure in-platform viewer for images, video, audio and text. The file
     is streamed only after an access check; there is no download link, and
     casual saving (right-click, drag, the player's download button) is
     turned off. Anything shown on screen can still be captured, so this
     deters copying rather than preventing it. --}}
@php
    $streamUrl = route('library.resources.files.stream', [$resource, $file]);
    // Secure files get the copy deterrents and a watermark with the viewer's name.
    $protected = $file->access_level->value === 'secure';
    $watermark = $protected ? auth()->user()->name.' · '.auth()->user()->email.' · '.now()->format('Y-m-d') : null;
@endphp

<x-app-layout :title="$file->title">
    <a href="{{ route('library.resources.show', $resource) }}" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-700">
        &larr; {{ $resource->title }}
    </a>

    <h1 class="mb-4 text-xl font-bold text-slate-900">{{ $file->title }}</h1>

    <div class="relative overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200/80 {{ $protected ? 'print:hidden' : '' }}" @if ($protected) data-protected data-watermark="{{ $watermark }}" @endif>
        @switch($viewer)
            @case('image')
                <div class="flex justify-center bg-slate-100 p-4">
                    <img src="{{ $streamUrl }}" alt="{{ $file->title }}" draggable="false" class="max-h-[80vh] w-auto select-none">
                </div>
                @break

            @case('video')
                <video controls controlslist="nodownload noremoteplayback" disablepictureinpicture playsinline preload="metadata" class="aspect-video w-full bg-black" src="{{ $streamUrl }}">
                    {{ __('Your browser can’t play this video.') }}
                </video>
                @break

            @case('audio')
                <div class="p-6">
                    <audio controls controlslist="nodownload" preload="metadata" class="w-full" src="{{ $streamUrl }}">
                        {{ __('Your browser can’t play this audio.') }}
                    </audio>
                </div>
                @break

            @case('text')
                <pre class="max-h-[75vh] overflow-auto p-6 text-sm leading-relaxed whitespace-pre-wrap text-slate-800 {{ $protected ? 'select-none' : 'select-text' }}">{{ $text['body'] }}</pre>
                @if ($text['truncated'])
                    <p class="border-t border-slate-100 px-6 py-3 text-xs text-slate-500">{{ __('Only the first part of this file is shown.') }}</p>
                @endif
                @break
        @endswitch
    </div>
    @if ($protected)
        <p class="hidden rounded-lg bg-slate-100 p-6 text-center text-sm text-slate-600 print:block">{{ __('This document can only be viewed on the platform.') }}</p>
    @endif

    @if ($protected)
        @push('scripts')
        <script src="{{ \App\Support\Asset::url('js/library-protect.js') }}"></script>
        @endpush
    @endif
</x-app-layout>
