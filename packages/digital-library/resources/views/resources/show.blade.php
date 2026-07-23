<x-app-layout :title="$resource->title">
    <a href="{{ route('library.resources.index') }}" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-700">
        &larr; Back to library
    </a>

    <x-card>
        <div class="flex h-14 w-14 items-center justify-center rounded-xl {{ \App\Enums\Module::Library->softClasses() }}">
            <x-module-icon module="library" class="h-7 w-7" />
        </div>

        <h1 class="mt-4 text-xl font-bold text-slate-900">{{ $resource->title }}</h1>
        @if ($resource->author)
            <p class="mt-1 text-sm text-slate-500">by {{ $resource->author }}</p>
        @endif

        <x-badge color="amber" class="mt-4">{{ $resource->category }}</x-badge>

        @if ($resource->description)
            <div class="rich-text mt-6 text-sm text-slate-700">{!! $resource->description !!}</div>
        @endif

        @if ($resource->external_url)
            <div class="mt-8">
                <x-button :href="$resource->external_url" target="_blank" rel="noopener noreferrer">
                    Open resource
                </x-button>
            </div>
        @endif
    </x-card>
</x-app-layout>
