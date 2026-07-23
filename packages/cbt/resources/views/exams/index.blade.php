<x-app-layout title="CBT">
    <x-page-header title="Exams" subtitle="Timed assessments with instant, auto-graded results.">
        @if (auth()->user()->isOwner())
            <x-slot:actions>
                <x-button :href="route('cbt.manage.exams.index')" variant="secondary">Manage exams</x-button>
            </x-slot:actions>
        @endif
    </x-page-header>

    @if ($exams->isEmpty())
        <x-empty-state
            title="No exams yet"
            description="Published exams will show up here."
        />
    @else
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($exams as $exam)
                <a href="{{ route('cbt.exams.show', $exam) }}" class="group block">
                    <x-card class="h-full transition hover:shadow-md hover:ring-slate-300">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl {{ \App\Enums\Module::Cbt->softClasses() }}">
                            <x-module-icon module="cbt" class="h-6 w-6" />
                        </div>
                        <h3 class="mt-4 text-base font-semibold text-slate-900">{{ $exam->title }}</h3>
                        <p class="mt-1 line-clamp-2 text-sm text-slate-500">{{ Illuminate\Support\Str::limit(strip_tags($exam->description), 140) }}</p>
                        <p class="mt-4 text-xs font-medium text-slate-400">
                            {{ $exam->questions_count }} {{ $exam->questions_count === 1 ? 'question' : 'questions' }}
                            &middot; {{ $exam->duration_minutes }} min
                        </p>
                    </x-card>
                </a>
            @endforeach
        </div>
    @endif
</x-app-layout>
