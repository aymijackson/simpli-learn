<x-app-layout :title="$resource->title">
    <a href="{{ route('library.manage.resources.index') }}" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-700">
        &larr; Back to resources
    </a>

    <x-page-header title="Edit resource" :subtitle="$resource->title" />

    <x-card>
        <form method="POST" action="{{ route('library.manage.resources.update', $resource) }}" class="space-y-5">
            @csrf
            @method('PUT')
            @include('library::manage.resources._form')
            <div class="flex items-center gap-4">
                <x-button type="submit">Save changes</x-button>
                @if ($resource->is_published)
                    <a href="{{ route('library.resources.show', $resource) }}" target="_blank" class="text-sm font-medium text-slate-500 underline hover:text-slate-700">
                        View live &rarr;
                    </a>
                @endif
            </div>
        </form>
    </x-card>
</x-app-layout>
