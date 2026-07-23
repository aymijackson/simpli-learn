<x-app-layout title="Edit Question">
    <a href="{{ route('cbt.manage.exams.edit', $exam) }}" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-700">
        &larr; {{ $exam->title }}
    </a>

    <x-page-header title="Edit question" />

    <x-card class="mb-8">
        <form method="POST" action="{{ route('cbt.manage.questions.update', [$exam, $question]) }}" class="space-y-5">
            @csrf
            @method('PUT')
            @include('cbt::manage.questions._form')
            <x-button type="submit">Save changes</x-button>
        </form>
    </x-card>

    <h2 class="mb-4 text-sm font-semibold text-slate-900">
        Answer options
        @if ($options->count() < 2)
            <span class="ml-1 font-normal text-amber-600">(add at least two, and mark one or more correct)</span>
        @endif
    </h2>

    <div class="space-y-4">
        @foreach ($options as $option)
            <x-card>
                <form method="POST" action="{{ route('cbt.manage.options.update', [$exam, $question, $option]) }}" class="space-y-3">
                    @csrf
                    @method('PUT')
                    <label class="flex items-center gap-1.5 text-xs font-medium text-slate-500">
                        <input
                            type="checkbox"
                            name="is_correct"
                            value="1"
                            class="rounded text-emerald-600 focus:ring-emerald-600"
                            @checked($option->is_correct)
                            onchange="this.form.requestSubmit()"
                        >
                        Correct
                    </label>
                    <x-editor name="option_text" :id="'editor-option-'.$option->id" :value="$option->option_text" />
                    <x-button type="submit" variant="secondary">Save</x-button>
                </form>
                <form method="POST" action="{{ route('cbt.manage.options.destroy', [$exam, $question, $option]) }}" onsubmit="return confirm('Delete this option?')" class="mt-2">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-500">Delete</button>
                </form>
            </x-card>
        @endforeach
    </div>

    <x-card class="mt-4">
        <form method="POST" action="{{ route('cbt.manage.options.store', [$exam, $question]) }}" class="space-y-3">
            @csrf
            <x-editor name="option_text" id="editor-option-new" label="New option" />
            <x-button type="submit" variant="secondary">Add option</x-button>
        </form>
    </x-card>
</x-app-layout>
