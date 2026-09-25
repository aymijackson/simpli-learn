<x-app-layout :title="$exam->title">
    <a href="{{ route('cbt.manage.exams.index') }}" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-700">
        &larr; Back to exams
    </a>

    <x-page-header title="Edit exam" :subtitle="$exam->title">
        <x-slot:actions>
            <x-button :href="route('cbt.manage.analytics.show', $exam)" variant="secondary">View analytics</x-button>
        </x-slot:actions>
    </x-page-header>

    <x-card class="mb-8">
        <form method="POST" action="{{ route('cbt.manage.exams.update', $exam) }}" class="space-y-5">
            @csrf
            @method('PUT')
            @include('cbt::manage.exams._form')
            <div class="flex items-center gap-4">
                <x-button type="submit">Save changes</x-button>
                @if ($exam->is_published)
                    <a href="{{ route('cbt.exams.show', $exam) }}" target="_blank" class="text-sm font-medium text-slate-500 underline hover:text-slate-700">
                        View live &rarr;
                    </a>
                @endif
            </div>
        </form>
    </x-card>

    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-900">Sections</h2>
    </div>

    @if ($sections->isEmpty())
        <x-empty-state title="No sections yet" description="Sections are optional — add one to group questions under shared instructions, then assign questions to it from each question's edit page." />
    @else
        <div class="mb-4 space-y-3">
            @foreach ($sections as $section)
                <x-card>
                    <form method="POST" action="{{ route('cbt.manage.sections.update', [$exam, $section]) }}" class="space-y-3">
                        @csrf
                        @method('PUT')
                        <div class="grid gap-3 sm:grid-cols-[2fr_1fr]">
                            <x-input type="text" name="title" label="Title" value="{{ $section->title }}" />
                            <x-input type="number" name="position" label="Position" value="{{ $section->position }}" min="0" />
                        </div>
                        <x-editor name="instructions" label="Section instructions (optional)" :value="$section->instructions" />
                        <x-button type="submit" variant="secondary">Save</x-button>
                    </form>
                    <form method="POST" action="{{ route('cbt.manage.sections.destroy', [$exam, $section]) }}" onsubmit="return confirm('Delete this section? Its questions will become unassigned.')" class="mt-2">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-500">Delete section</button>
                    </form>
                </x-card>
            @endforeach
        </div>
    @endif

    <x-card class="mb-8">
        <form method="POST" action="{{ route('cbt.manage.sections.store', $exam) }}" class="grid gap-3 sm:grid-cols-[2fr_1fr_auto] sm:items-end">
            @csrf
            <x-input type="text" name="title" label="New section title" required />
            <x-input type="number" name="position" label="Position" value="{{ $sections->count() }}" min="0" required />
            <x-button type="submit" variant="secondary">Add section</x-button>
        </form>
    </x-card>

    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-900">Questions</h2>
        <div class="flex items-center gap-3">
            <x-button :href="route('cbt.manage.questions.import.create', $exam)" variant="secondary">Import CSV</x-button>
            <x-button :href="route('cbt.manage.questions.create', $exam)">Add question</x-button>
        </div>
    </div>

    @if ($questions->isEmpty())
        <x-empty-state title="No questions yet" />
    @else
        <x-card :padded="false">
            <ul class="divide-y divide-slate-200">
                @foreach ($questions as $question)
                    <li class="flex items-center justify-between gap-4 px-6 py-4">
                        <div class="flex items-center gap-3">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-semibold text-slate-500">{{ $loop->iteration }}</span>
                            <div>
                                <p class="text-sm font-medium text-slate-900">{{ \Illuminate\Support\Str::limit(strip_tags($question->question_text), 80) }}</p>
                                @if ($question->options_count < 2)
                                    <p class="text-xs text-amber-600">Needs at least two answer options</p>
                                @else
                                    <p class="text-xs text-slate-500">
                                        {{ $question->options_count }} options
                                        &middot; {{ $question->answer_type->label() }}
                                        &middot; {{ $question->points }} {{ \Illuminate\Support\Str::plural('point', $question->points) }}
                                        @if ($question->section)
                                            &middot; {{ $question->section->title }}
                                        @endif
                                    </p>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <a href="{{ route('cbt.manage.questions.edit', [$exam, $question]) }}" class="text-sm font-medium text-brand-600 hover:text-brand-500">Edit</a>
                            <form method="POST" action="{{ route('cbt.manage.questions.destroy', [$exam, $question]) }}" onsubmit="return confirm('Delete this question?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-500">Delete</button>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ul>
        </x-card>
    @endif
</x-app-layout>
