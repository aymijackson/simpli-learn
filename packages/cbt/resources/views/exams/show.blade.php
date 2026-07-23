<x-app-layout :title="$exam->title">
    <a href="{{ route('cbt.exams.index') }}" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-700">
        &larr; Back to exams
    </a>

    <x-page-header :title="$exam->title">
        <x-slot:actions>
            @if ($startBlockReason)
                <x-badge color="red">{{ $startBlockReason }}</x-badge>
            @else
                <form method="POST" action="{{ route('cbt.exams.start', $exam) }}">
                    @csrf
                    <x-button type="submit">{{ $attempts->isEmpty() ? 'Start exam' : 'Retake exam' }}</x-button>
                </form>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if ($exam->description)
        <div class="rich-text mb-8 text-sm text-slate-600">{!! $exam->description !!}</div>
    @endif

    @php($totalQuestions = $exam->questions()->count())
    @php($effectiveQuestions = $exam->questions_per_attempt ? min($exam->questions_per_attempt, $totalQuestions) : $totalQuestions)

    <div class="mb-8 grid grid-cols-2 gap-4 sm:grid-cols-4">
        <x-card class="text-center">
            <p class="text-2xl font-bold text-slate-900">{{ $effectiveQuestions }}</p>
            <p class="text-xs text-slate-500">
                Questions
                @if ($exam->questions_per_attempt && $exam->questions_per_attempt < $totalQuestions)
                    <span class="block">(random, of {{ $totalQuestions }})</span>
                @endif
            </p>
        </x-card>
        <x-card class="text-center">
            <p class="text-2xl font-bold text-slate-900">{{ $exam->duration_minutes }}</p>
            <p class="text-xs text-slate-500">Minutes</p>
        </x-card>
        <x-card class="text-center">
            <p class="text-2xl font-bold text-slate-900">{{ $exam->pass_percentage }}%</p>
            <p class="text-xs text-slate-500">Pass mark</p>
        </x-card>
        <x-card class="text-center">
            <p class="text-2xl font-bold text-slate-900">{{ $attempts->count() }}{{ $exam->max_attempts ? ' / '.$exam->max_attempts : '' }}</p>
            <p class="text-xs text-slate-500">Your attempts</p>
        </x-card>
    </div>

    @if ($exam->available_from || $exam->available_until || ! $exam->allow_retakes)
        <div class="mb-8 flex flex-wrap gap-2 text-xs text-slate-500">
            @if ($exam->available_from)
                <x-badge color="slate">Opens {{ $exam->available_from->format('M j, Y g:ia') }}</x-badge>
            @endif
            @if ($exam->available_until)
                <x-badge color="slate">Closes {{ $exam->available_until->format('M j, Y g:ia') }}</x-badge>
            @endif
            @unless ($exam->allow_retakes)
                <x-badge color="slate">One attempt only</x-badge>
            @endunless
        </div>
    @endif

    @if ($attempts->isNotEmpty())
        <x-card :padded="false">
            <ul class="divide-y divide-slate-200">
                @foreach ($attempts as $attempt)
                    <li class="flex items-center justify-between gap-4 px-6 py-4">
                        <div>
                            <p class="text-sm font-medium text-slate-900">{{ $attempt->started_at->format('M j, Y g:ia') }}</p>
                            <p class="text-xs text-slate-500">
                                {{ $attempt->isSubmitted() ? 'Submitted '.$attempt->submitted_at->diffForHumans() : 'In progress' }}
                            </p>
                        </div>
                        @if ($attempt->isSubmitted())
                            <a href="{{ route('cbt.attempts.result', $attempt) }}" class="flex items-center gap-3">
                                <x-badge :color="$attempt->passed() ? 'green' : 'red'">
                                    {{ $attempt->score }}% &middot; {{ $attempt->passed() ? 'Passed' : 'Failed' }}
                                </x-badge>
                            </a>
                        @else
                            <a href="{{ route('cbt.attempts.take', $attempt) }}" class="text-sm font-medium {{ \App\Enums\Module::Cbt->textClasses() }}">Resume</a>
                        @endif
                    </li>
                @endforeach
            </ul>
        </x-card>
    @endif
</x-app-layout>
