<x-app-layout :title="$exam->title">
    <a href="{{ route('cbt.exams.show', $exam) }}" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-700">
        &larr; {{ $exam->title }}
    </a>

    <x-card class="mb-8 text-center">
        @if ($attempt->isAwaitingMarking())
            <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-amber-50 text-amber-600"><x-icon name="clock" class="h-7 w-7" /></span>
            <p class="mt-4 text-2xl font-bold text-slate-900">Submitted — awaiting marking</p>
            <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">
                Some of your answers are marked by hand. Your score and result will appear here once marking is finished — we'll email you when it's ready.
            </p>
        @else
            <p class="text-sm font-medium text-slate-500">Your score</p>
            <p class="mt-2 text-5xl font-bold {{ $attempt->passed() ? 'text-emerald-600' : 'text-red-600' }}">
                {{ $attempt->score }}%
            </p>
            <div class="mt-4">
                <x-badge :color="$attempt->passed() ? 'green' : 'red'">
                    {{ $attempt->passed() ? 'Passed' : 'Failed' }} &middot; pass mark {{ $exam->pass_percentage }}%
                </x-badge>
            </div>
        @endif

        @if ($attempt->passed() && $exam->certificatePolicy()->value !== 'none')
            <div class="mt-6 flex items-center justify-center gap-3">
                @if ($attempt->certificate)
                    <x-button :href="route('cbt.attempts.certificate.download', $attempt)" variant="secondary">
                        Download certificate
                        @if ($attempt->certificate->tier->value === 'unverified')
                            (unverified)
                        @endif
                    </x-button>
                    @if ($attempt->certificate->tier->value === 'unverified')
                        <x-button :href="route('cbt.attempts.certificate-payment.create', $attempt)">Upgrade to verified</x-button>
                    @endif
                @elseif ($exam->certificatePolicy()->value === 'paid')
                    <x-button :href="route('cbt.attempts.certificate-payment.create', $attempt)">Get certificate</x-button>
                @endif
            </div>
        @endif
    </x-card>

    <h2 class="mb-4 text-sm font-semibold text-slate-900">Answer review</h2>
    @if ($attempt->answers->isEmpty())
        <x-empty-state title="No answers were recorded for this attempt." />
    @endif
    <div class="space-y-4">
        @foreach ($attempt->answers as $answer)
            @php
                $question = $answer->question;
                $type = $question->answer_type;
                $questionScore = $question->scoreAnswer($answer);
                [$badgeColor, $badgeLabel] = match (true) {
                    $questionScore === null => ['amber', 'Awaiting marking'],
                    $type === \Elibrary\Cbt\Enums\AnswerType::Essay => [$questionScore >= 1.0 ? 'green' : ($questionScore <= 0.0 ? 'red' : 'amber'), 'Marked'],
                    $questionScore >= 1.0 => ['green', 'Correct'],
                    $questionScore <= 0.0 => ['red', 'Incorrect'],
                    default => ['amber', 'Partial credit'],
                };
                $correctOptions = $question->options->where('is_correct', true);
            @endphp
            <x-card>
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Question {{ $loop->iteration }} &middot; {{ $question->points }} {{ \Illuminate\Support\Str::plural('point', $question->points) }}
                        </p>
                        <div class="rich-text mt-1 text-sm font-semibold text-slate-900">{!! $question->question_text !!}</div>
                    </div>
                    <x-badge :color="$badgeColor">{{ $badgeLabel }}</x-badge>
                </div>
                <div class="mt-3 space-y-1 text-sm">
                    @if ($type === \Elibrary\Cbt\Enums\AnswerType::Essay)
                        <p class="text-slate-600">Your answer:</p>
                        <p class="rounded-lg bg-slate-50 p-3 whitespace-pre-line text-slate-800 ring-1 ring-slate-200">{{ $answer->text_response ?: 'No answer' }}</p>
                        @if ($questionScore !== null && filled($answer->text_response))
                            <p class="pt-2 font-medium text-slate-700">{{ rtrim(rtrim(number_format((float) $answer->awarded_points, 2), '0'), '.') }} of {{ $question->points }} {{ \Illuminate\Support\Str::plural('point', $question->points) }}</p>
                        @endif
                        @if ($answer->feedback)
                            <p class="rounded-lg bg-brand-50 p-3 text-brand-900 ring-1 ring-brand-100"><span class="font-semibold">Feedback:</span> {{ $answer->feedback }}</p>
                        @endif
                    @elseif ($type === \Elibrary\Cbt\Enums\AnswerType::ShortAnswer)
                        <p class="text-slate-600">
                            Your answer:
                            <span class="font-medium {{ $questionScore >= 1.0 ? 'text-emerald-700' : 'text-red-700' }}">{{ $answer->text_response ?: 'No answer' }}</span>
                        </p>
                        @if ($questionScore < 1.0)
                            <p class="text-slate-600">
                                Accepted answer{{ $correctOptions->count() > 1 ? 's' : '' }}:
                                <span class="font-medium text-emerald-700">{{ $correctOptions->map(fn ($option) => strip_tags($option->option_text))->implode(', ') }}</span>
                            </p>
                        @endif
                    @else
                        <p class="text-slate-600">
                            Your answer:
                            <span class="rich-text font-medium {{ $questionScore >= 1.0 ? 'text-emerald-700' : 'text-red-700' }}">
                                @forelse ($answer->selectedOptions as $selected)
                                    {!! $selected->option_text !!}@if (! $loop->last), @endif
                                @empty
                                    No answer
                                @endforelse
                            </span>
                        </p>
                        @if ($questionScore < 1.0)
                            <p class="text-slate-600">
                                Correct answer{{ $correctOptions->count() > 1 ? 's' : '' }}:
                                <span class="rich-text font-medium text-emerald-700">
                                    @foreach ($correctOptions as $correct)
                                        {!! $correct->option_text !!}@if (! $loop->last), @endif
                                    @endforeach
                                </span>
                            </p>
                        @endif
                    @endif
                </div>
            </x-card>
        @endforeach
    </div>
</x-app-layout>
