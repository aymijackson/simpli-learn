{{-- Secure in-platform viewer for images, video, audio and text. The file
     is streamed only after an access check; there is no download link, and
     casual saving (right-click, drag, the player's download button) is
     turned off. Anything shown on screen can still be captured, so this
     deters copying rather than preventing it. --}}
@php($streamUrl = route('library.resources.files.stream', [$resource, $file]))
<x-app-layout :title="$file->title">
    <a href="{{ route('library.resources.show', $resource) }}" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-700">
        &larr; {{ $resource->title }}
    </a>

    <h1 class="mb-4 text-xl font-bold text-slate-900">{{ $file->title }}</h1>

    <div class="overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200/80" oncontextmenu="return false">
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
                <pre class="max-h-[75vh] overflow-auto p-6 text-sm leading-relaxed whitespace-pre-wrap text-slate-800 select-text">{{ $text['body'] }}</pre>
                @if ($text['truncated'])
                    <p class="border-t border-slate-100 px-6 py-3 text-xs text-slate-500">{{ __('Only the first part of this file is shown.') }}</p>
                @endif
                @break
        @endswitch
    </div>
</x-app-layout>
