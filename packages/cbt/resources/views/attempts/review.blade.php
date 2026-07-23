<x-app-layout :title="$exam->title">
    <div class="mb-6">
        <h1 class="text-xl font-bold text-slate-900">{{ $exam->title }}</h1>
        <p class="text-sm text-slate-500">Review your answers, then submit.</p>
    </div>

    @include('cbt::attempts._progress_strip')

    <x-card :padded="false" class="mb-6">
        <ul class="divide-y divide-slate-200">
            @foreach ($rows as $row)
                <li class="flex items-center justify-between gap-4 px-6 py-3">
                    <div class="flex items-center gap-3">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-semibold {{ $row['answered'] ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                            {{ $row['page'] }}
                        </span>
                        <span class="rich-text text-sm text-slate-700">{!! \Illuminate\Support\Str::limit(strip_tags($row['question']->question_text), 80) !!}</span>
                        @if ($row['flagged'])
                            <x-badge color="amber">Flagged</x-badge>
                        @endif
                    </div>
                    <div class="flex items-center gap-3">
                        @if ($row['answered'])
                            <x-badge color="green">Answered</x-badge>
                        @else
                            <x-badge color="slate">Unanswered</x-badge>
                        @endif
                        @if ($exam->allow_backward_navigation)
                            <a href="{{ route('cbt.attempts.questions.show', [$attempt, $row['page']]) }}" class="text-sm font-medium text-brand-600 hover:text-brand-500">Edit</a>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    </x-card>

    <form method="POST" action="{{ route('cbt.attempts.submit', $attempt) }}">
        @csrf
        <x-button type="submit" class="w-full">Submit exam</x-button>
    </form>
</x-app-layout>
