<x-app-layout title="Manage Courses">
    <x-page-header title="Manage courses" subtitle="Create, edit, and publish your organization's courses.">
        <x-slot:actions>
            <x-button :href="route('lms.manage.payment-gateways.edit')" variant="secondary">Payment gateways</x-button>
            <x-button :href="route('lms.manage.course-purchases.index')" variant="secondary">Purchases</x-button>
            <x-button :href="route('lms.manage.courses.create')">New course</x-button>
        </x-slot:actions>
    </x-page-header>

    @if ($courses->isEmpty())
        <x-empty-state title="No courses yet" description="Create your first course to get started." />
    @else
        <x-card :padded="false">
            <ul class="divide-y divide-slate-200">
                @foreach ($courses as $course)
                    <li class="flex items-center justify-between gap-4 px-6 py-4">
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ $course->title }}</p>
                            <p class="text-sm text-slate-500">
                                {{ $course->lessons_count }} {{ $course->lessons_count === 1 ? 'lesson' : 'lessons' }}
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <x-badge :color="$course->is_published ? 'green' : 'slate'">{{ $course->is_published ? 'Published' : 'Draft' }}</x-badge>
                            <a href="{{ route('lms.manage.courses.edit', $course) }}" class="text-sm font-medium text-brand-600 hover:text-brand-500">Edit</a>
                            <form method="POST" action="{{ route('lms.manage.courses.destroy', $course) }}" onsubmit="return confirm('Delete this course and all its lessons?')">
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
