<x-app-layout :title="$resource->title">
    <a href="{{ route('library.manage.resources.index') }}" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-700">
        &larr; Back to resources
    </a>

    <x-page-header title="Edit resource" :subtitle="$resource->title">
        @if ($resource->requires_checkout)
            <x-slot:actions>
                <x-button :href="route('library.manage.resources.checkouts.index', $resource)" variant="secondary">Checkouts &amp; waitlist</x-button>
            </x-slot:actions>
        @endif
    </x-page-header>

    <x-card class="mb-8">
        <form method="POST" action="{{ route('library.manage.resources.update', $resource) }}" enctype="multipart/form-data" class="space-y-5">
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

    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-900">Files</h2>
    </div>

    @if ($resource->files->isEmpty())
        <x-empty-state title="No files yet" />
    @else
        <x-card :padded="false" class="mb-4">
            <ul class="divide-y divide-slate-200">
                @foreach ($resource->files as $file)
                    <li class="flex items-center justify-between gap-4 px-6 py-4">
                        <div>
                            <p class="text-sm font-medium text-slate-900">{{ $file->title }}</p>
                            <p class="text-xs text-slate-500">{{ $file->format->label() }} &middot; {{ $file->access_level->label() }} &middot; {{ number_format($file->size / 1024, 0) }} KB</p>
                        </div>
                        <form method="POST" action="{{ route('library.manage.resources.files.destroy', [$resource, $file]) }}" onsubmit="return confirm('Delete this file?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-500">Delete</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        </x-card>
    @endif

    <x-card>
        <form method="POST" action="{{ route('library.manage.resources.files.store', $resource) }}" enctype="multipart/form-data" class="grid gap-3 sm:grid-cols-[2fr_1fr_1fr_2fr_auto] sm:items-end">
            @csrf
            <x-input type="text" name="title" label="Title" required />
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Format</label>
                <select name="format" class="block w-full rounded-lg border-0 px-3 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:text-sm">
                    @foreach (\Elibrary\Library\Enums\LibraryFileFormat::cases() as $format)
                        <option value="{{ $format->value }}">{{ $format->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Access</label>
                <select name="access_level" class="block w-full rounded-lg border-0 px-3 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:text-sm">
                    @foreach (\Elibrary\Library\Enums\LibraryFileAccessLevel::cases() as $level)
                        <option value="{{ $level->value }}" @selected($level->value === 'secure')>{{ $level->label() }}</option>
                    @endforeach
                </select>
            </div>
            <input type="file" name="file" required class="block w-full text-sm text-slate-900 file:mr-4 file:rounded-lg file:border-0 file:bg-slate-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-100">
            <x-button type="submit" variant="secondary">Upload</x-button>
        </form>
        <p class="mt-2 text-xs text-slate-500">PDF/EPUB get an embedded in-platform reader; audio gets a player; other files are viewable/downloadable per their access level. Max 500MB.</p>
    </x-card>
</x-app-layout>
