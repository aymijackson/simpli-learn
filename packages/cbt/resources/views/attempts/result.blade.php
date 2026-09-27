<x-app-layout :title="$exam->title">
    <a href="{{ $placement['url'] ?? route('cbt.exams.show', $exam) }}" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-700">
        &larr; {{ $placement['title'] ?? $exam->title }}
    </a>

    <h1 class="sr-only">{{ __(':title — your result', ['title' => $exam->title]) }}</h1>

    <x-card class="mb-8 text-center">
        @if ($attempt->isAwaitingMarking())
            <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-amber-50 text-amber-600"><x-icon name="clock" class="h-7 w-7" /></span>
            <p class="mt-4 text-2xl font-bold text-slate-900">{{ __('Submitted — awaiting marking') }}</p>
            <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">
                {{ __('Some of your answers are marked by hand. Your score and result will appear here once marking is finished — we\'ll email you when it\'s ready.') }}
            </p>
        @else
            <p class="text-sm font-medium text-slate-500">{{ __('Your score') }}</p>
            <p class="mt-2 text-5xl font-bold {{ $attempt->passed() ? 'text-emerald-600' : 'text-red-600' }}">
                {{ $attempt->score }}%
            </p>
            <div class="mt-4">
                <x-badge :color="$attempt->passed() ? 'green' : 'red'">
                    {{ __(':passed · pass mark :pass_percentage%', ['passed' => $attempt->passed() ? __('Passed') : __('Failed'), 'pass_percentage' => $exam->pass_percentage]) }}
                </x-badge>
            </div>
        @endif

        @if ($attempt->passed() && $exam->certificatePolicy()->value !== 'none')
            <div class="mt-6 flex items-center justify-center gap-3">
                @if ($attempt->certificate)
                    <x-button :href="route('cbt.attempts.certificate.download', $attempt)" variant="secondary">
                        {{ __('Download certificate') }}
                        @if ($attempt->certificate->tier->value === 'unverified')
                            {{ __('(unverified)') }}
                        @endif
                    </x-button>
                    @if ($attempt->certificate->tier->value === 'unverified')
                        <x-button :href="route('cbt.attempts.certificate-payment.create', $attempt)">{{ __('Upgrade to verified') }}</x-button>
                    @endif
                @elseif ($exam->certificatePolicy()->value === 'paid')
                    <x-button :href="route('cbt.attempts.certificate-payment.create', $attempt)">{{ __('Get certificate') }}</x-button>
                @endif
            </div>
        @endif

        @if ($placement)
            <div class="mt-6 flex flex-col items-center gap-2 border-t border-slate-100 pt-6">
                <x-button :href="$placement['next_url']" icon-right="arrow-right">{{ $placement['next_label'] }}</x-button>
                <p class="text-xs text-slate-500">{{ __(':checkpoint in :course', ['checkpoint' => $placement['checkpoint'], 'course' => $placement['title']]) }}</p>
            </div>
        @endif
    </x-card>

    <h2 class="mb-4 text-sm font-semibold text-slate-900">{{ __('Answer review') }}</h2>
    @if ($attempt->answers->isEmpty())
        <x-empty-state :title="__('No answers were recorded for this attempt.')" />
    @endif
    <div class="space-y-4">
        @foreach ($attempt->answers as $answer)
            @php
                $question = $answer->question;
                $type = $question->answer_type;
                $questionScore = $question->scoreAnswer($answer);
                [$badgeColor, $badgeLabel] = match (true) {
                    $questionScore === null => ['amber', __('Awaiting marking')],
                    $type === \Elibrary\Cbt\Enums\AnswerType::Essay => [$questionScore >= 1.0 ? 'green' : ($questionScore <= 0.0 ? 'red' : 'amber'), __('Marked')],
                    $questionScore >= 1.0 => ['green', __('Correct')],
                    $questionScore <= 0.0 => ['red', __('Incorrect')],
                    default => ['amber', __('Partial credit')],
                };
                $correctOptions = $question->options->where('is_correct', true);
            @endphp
            <x-card>
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            {{ __('Question :iteration · :points :noun', ['iteration' => $loop->iteration, 'points' => $question->points, 'noun' => trans_choice('point|points', $question->points)]) }}
                        </p>
                        <div class="rich-text mt-1 text-sm font-semibold text-slate-900">{!! $question->question_text !!}</div>
                    </div>
                    <x-badge :color="$badgeColor">{{ $badgeLabel }}</x-badge>
                </div>
                <div class="mt-3 space-y-1 text-sm">
                    @if ($type === \Elibrary\Cbt\Enums\AnswerType::Essay)
                        <p class="text-slate-600">{{ __('Your answer:') }}</p>
                        <p class="rounded-lg bg-slate-50 p-3 whitespace-pre-line text-slate-800 ring-1 ring-slate-200">{{ $answer->text_response ?: __('No answer') }}</p>
                        @if ($questionScore !== null && filled($answer->text_response))
                            <p class="pt-2 font-medium text-slate-700">{{ __(':awarded_points of :points :noun', ['awarded_points' => rtrim(rtrim(number_format((float) $answer->awarded_points, 2), '0'), '.'), 'points' => $question->points, 'noun' => trans_choice('point|points', $question->points)]) }}</p>
                        @endif
                        @if ($answer->feedback)
                            <p class="rounded-lg bg-brand-50 p-3 text-brand-900 ring-1 ring-brand-100"><span class="font-semibold">{{ __('Feedback:') }}</span> {{ $answer->feedback }}</p>
                        @endif
                    @elseif ($type === \Elibrary\Cbt\Enums\AnswerType::ShortAnswer)
                        <p class="text-slate-600">
                            {{ __('Your answer:') }}
                            <span class="font-medium {{ $questionScore >= 1.0 ? 'text-emerald-700' : 'text-red-700' }}">{{ $answer->text_response ?: __('No answer') }}</span>
                        </p>
                        @if ($questionScore < 1.0)
                            <p class="text-slate-600">
                                {{ ($correctOptions->count() > 1 ? __('Accepted answers:') : __('Accepted answer:')) }}
                                <span class="font-medium text-emerald-700">{{ $correctOptions->map(fn ($option) => strip_tags($option->option_text))->implode(', ') }}</span>
                            </p>
                        @endif
                    @else
                        <p class="text-slate-600">
                            {{ __('Your answer:') }}
                            <span class="rich-text font-medium {{ $questionScore >= 1.0 ? 'text-emerald-700' : 'text-red-700' }}">
                                @forelse ($answer->selectedOptions as $selected)
                                    {!! $selected->option_text !!}@if (! $loop->last), @endif
                                @empty
                                    {{ __('No answer') }}
                                @endforelse
                            </span>
                        </p>
                        @if ($questionScore < 1.0)
                            <p class="text-slate-600">
                                {{ ($correctOptions->count() > 1 ? __('Correct answers:') : __('Correct answer:')) }}
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
