<x-app-layout :title="$exam->title">
    <a href="{{ route('cbt.exams.show', $exam) }}" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-700">
        &larr; {{ $exam->title }}
    </a>

    <x-card class="mb-8 text-center">
        <p class="text-sm font-medium text-slate-500">Your score</p>
        <p class="mt-2 text-5xl font-bold {{ $attempt->passed() ? 'text-emerald-600' : 'text-red-600' }}">
            {{ $attempt->score }}%
        </p>
        <div class="mt-4">
            <x-badge :color="$attempt->passed() ? 'green' : 'red'">
                {{ $attempt->passed() ? 'Passed' : 'Failed' }} &middot; pass mark {{ $exam->pass_percentage }}%
            </x-badge>
        </div>

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
            @php($correctOptions = $answer->question->options->where('is_correct', true))
            @php($questionScore = $answer->question->scoreForSelection($answer->selectedOptions->pluck('id')))
            @php($badgeColor = $questionScore >= 1.0 ? 'green' : ($questionScore <= 0.0 ? 'red' : 'amber'))
            @php($badgeLabel = $questionScore >= 1.0 ? 'Correct' : ($questionScore <= 0.0 ? 'Incorrect' : 'Partial credit'))
            <x-card>
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Question {{ $loop->iteration }} &middot; {{ $answer->question->points }} {{ \Illuminate\Support\Str::plural('point', $answer->question->points) }}
                        </p>
                        <div class="rich-text mt-1 text-sm font-semibold text-slate-900">{!! $answer->question->question_text !!}</div>
                    </div>
                    <x-badge :color="$badgeColor">{{ $badgeLabel }}</x-badge>
                </div>
                <div class="mt-3 space-y-1 text-sm">
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
                </div>
            </x-card>
        @endforeach
    </div>
</x-app-layout>
