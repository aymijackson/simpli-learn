<x-app-layout :title="'Mark — '.$attempt->user?->name">
    <a href="{{ route('cbt.manage.marking.index') }}" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-800">
        <x-icon name="arrow-left" class="h-4 w-4" /> Marking queue @if ($queue)({{ $queue }})@endif
    </a>

    <x-page-header :title="$attempt->user?->name ?? 'Former member'" :eyebrow="$attempt->exam->title"
        :subtitle="'Submitted '.$attempt->submitted_at->format('M j, Y g:ia').' · pass mark '.$attempt->exam->pass_percentage.'%'">
        <x-slot:actions>
            @if ($attempt->isAwaitingMarking())
                <x-badge color="amber">Awaiting marking</x-badge>
            @else
                <x-badge :color="$attempt->passed() ? 'green' : 'red'">{{ $attempt->score }}% &middot; {{ $attempt->passed() ? 'Passed' : 'Not passed' }}</x-badge>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if ($essays->isEmpty())
        <x-empty-state icon="check" title="No written answers" description="This attempt has no essay answers to mark." />
    @else
        <form method="POST" action="{{ route('cbt.manage.marking.update', $attempt) }}" class="space-y-6">
            @csrf
            @method('PUT')

            @foreach ($essays as $row)
                @php($question = $row['question'])
                @php($answer = $row['answer'])
                <x-card>
                    <div class="flex items-start justify-between gap-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Question {{ $loop->iteration }} &middot; {{ $question->points }} {{ \Illuminate\Support\Str::plural('point', $question->points) }}</p>
                        @if ($answer->marked_at)
                            <x-badge color="green">Marked by {{ $answer->markedBy?->name ?? 'someone' }}</x-badge>
                        @endif
                    </div>
                    <div class="rich-text mt-2 text-sm text-slate-900">{!! $question->question_text !!}</div>

                    @if ($question->marking_guide)
                        <div class="mt-4 rounded-lg bg-sky-50 px-4 py-3 text-sm text-sky-900 ring-1 ring-sky-100">
                            <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-sky-700">Marking guide</p>
                            <p class="whitespace-pre-line">{{ $question->marking_guide }}</p>
                        </div>
                    @endif

                    <div class="mt-4 rounded-lg bg-slate-50 px-4 py-3 ring-1 ring-slate-200">
                        <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Answer &middot; {{ str_word_count($answer->text_response) }} words</p>
                        <p class="whitespace-pre-line text-sm leading-relaxed text-slate-800">{{ $answer->text_response }}</p>
                    </div>

                    <div class="mt-4 grid gap-4 sm:grid-cols-[10rem_1fr]">
                        <div>
                            <label for="points-{{ $answer->id }}" class="mb-1.5 block text-sm font-medium text-slate-700">Points (0–{{ $question->points }})</label>
                            <input id="points-{{ $answer->id }}" type="number" name="points[{{ $answer->id }}]" min="0" max="{{ $question->points }}" step="0.5" required
                                   value="{{ old('points.'.$answer->id, $answer->awarded_points) }}"
                                   class="block w-full rounded-lg border-0 px-3 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:text-sm">
                            @error('points.'.$answer->id)<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="feedback-{{ $answer->id }}" class="mb-1.5 block text-sm font-medium text-slate-700">Feedback for the learner (optional)</label>
                            <textarea id="feedback-{{ $answer->id }}" name="feedback[{{ $answer->id }}]" rows="3" maxlength="2000"
                                      class="block w-full rounded-lg border-0 px-3 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:text-sm">{{ old('feedback.'.$answer->id, $answer->feedback) }}</textarea>
                            @error('feedback.'.$answer->id)<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </x-card>
            @endforeach

            <div class="flex items-center justify-end gap-3">
                <p class="text-xs text-slate-500">Saving re-calculates the score{{ $attempt->isAwaitingMarking() ? ' and emails the learner their result' : '' }}.</p>
                <x-button type="submit" icon="check">Save marks</x-button>
            </div>
        </form>
    @endif
</x-app-layout>
