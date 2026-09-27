<x-app-layout :title="$isOwner ? 'Manage Courses' : 'My courses'">
    @if ($isOwner)
        <x-page-header title="Manage courses" subtitle="Create, edit, and publish your organization's courses.">
            <x-slot:actions>
                <x-button :href="route('lms.manage.payment-gateways.edit')" variant="secondary">Payment gateways</x-button>
                <x-button :href="route('lms.manage.course-purchases.index')" variant="secondary">Purchases</x-button>
                <x-button :href="route('lms.manage.courses.create')">New course</x-button>
            </x-slot:actions>
        </x-page-header>
    @else
        <x-page-header title="My courses" subtitle="The courses you teach: edit their lessons, follow learners' progress and mark written answers." />
    @endif

    @if ($courses->isEmpty())
        <x-empty-state title="No courses yet" :description="$isOwner ? 'Create your first course to get started.' : 'You haven\'t been assigned any courses to teach yet.'" />
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
                            @if ($course->is_public)
                                <x-badge color="brand" icon="globe">All workspaces</x-badge>
                            @endif
                            <x-badge :color="$course->is_published ? 'green' : 'slate'">{{ $course->is_published ? 'Published' : 'Draft' }}</x-badge>
                            <a href="{{ route('lms.manage.courses.report', $course) }}" class="inline-flex items-center gap-1 text-sm font-medium text-brand-600 hover:text-brand-500"><x-icon name="chart-bar" class="h-4 w-4" /> {{ $isOwner ? 'Report & assign' : 'Report' }}</a>
                            <a href="{{ route('lms.manage.courses.edit', $course) }}" class="text-sm font-medium text-brand-600 hover:text-brand-500">Edit</a>
                            @if ($isOwner)
                            <form method="POST" action="{{ route('lms.manage.courses.destroy', $course) }}" onsubmit="return confirm('{{ $course->is_public ? 'This course is open to every workspace — deleting it removes it, and everyone\'s progress in it, everywhere. ' : '' }}Delete this course and all its lessons?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-500">Delete</button>
                            </form>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        </x-card>
    @endif

    @if ($sharedCourses->isNotEmpty())
        <h2 class="mt-10 mb-1 text-base font-semibold text-slate-900">Shared with your workspace</h2>
        <p class="mb-4 text-sm text-slate-500">Courses other workspaces make available here. Your learners take them free; you can assign them and follow progress, but not edit them.</p>
        <x-card :padded="false">
            <ul class="divide-y divide-slate-200">
                @foreach ($sharedCourses as $shared)
                    <li class="flex items-center justify-between gap-4 px-6 py-4">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-900">{{ $shared->title }}</p>
                            <p class="text-sm text-slate-500">From {{ $shared->tenant?->name ?? 'another workspace' }}</p>
                        </div>
                        <div class="flex shrink-0 items-center gap-3">
                            <a href="{{ route('lms.courses.show', $shared) }}" class="text-sm font-medium text-brand-600 hover:text-brand-500">View</a>
                            <a href="{{ route('lms.manage.courses.report', $shared) }}" class="inline-flex items-center gap-1 text-sm font-medium text-brand-600 hover:text-brand-500"><x-icon name="chart-bar" class="h-4 w-4" /> Report &amp; assign</a>
                        </div>
                    </li>
                @endforeach
            </ul>
        </x-card>
    @endif
</x-app-layout>
