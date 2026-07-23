<x-app-layout title="Learning">
    <x-page-header title="Courses" subtitle="Browse what's available and track your progress.">
        @if (auth()->user()->isOwner())
            <x-slot:actions>
                <x-button :href="route('lms.manage.courses.index')" variant="secondary">Manage courses</x-button>
            </x-slot:actions>
        @endif
    </x-page-header>

    @if ($courses->isEmpty())
        <x-empty-state
            title="No courses yet"
            description="Published courses will show up here."
        />
    @else
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($courses as $course)
                <a href="{{ route('lms.courses.show', $course) }}" class="group block">
                    <x-card class="h-full transition hover:shadow-md hover:ring-slate-300">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl {{ \App\Enums\Module::Lms->softClasses() }}">
                            <x-module-icon module="lms" class="h-6 w-6" />
                        </div>
                        <h3 class="mt-4 text-base font-semibold text-slate-900">{{ $course->title }}</h3>
                        <p class="mt-1 line-clamp-2 text-sm text-slate-500">{{ Illuminate\Support\Str::limit(strip_tags($course->description), 140) }}</p>
                        <p class="mt-4 text-xs font-medium text-slate-400">
                            {{ $course->lessons_count }} {{ $course->lessons_count === 1 ? 'lesson' : 'lessons' }}
                        </p>
                    </x-card>
                </a>
            @endforeach
        </div>
    @endif
</x-app-layout>
