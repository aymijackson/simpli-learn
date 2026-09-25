<x-app-layout title="Manage Exams">
    <x-page-header title="Manage exams" subtitle="Create, edit, and publish your organization's exams.">
        <x-slot:actions>
            <x-button :href="route('cbt.manage.certificates.settings.edit')" variant="secondary">Certificates</x-button>
            <x-button :href="route('cbt.manage.analytics.index')" variant="secondary">Analytics</x-button>
            <x-button :href="route('cbt.manage.exams.create')">New exam</x-button>
        </x-slot:actions>
    </x-page-header>

    @if ($exams->isEmpty())
        <x-empty-state title="No exams yet" description="Create your first exam to get started." />
    @else
        <x-card :padded="false">
            <ul class="divide-y divide-slate-200">
                @foreach ($exams as $exam)
                    <li class="flex items-center justify-between gap-4 px-6 py-4">
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ $exam->title }}</p>
                            <p class="text-sm text-slate-500">
                                {{ $exam->questions_count }} {{ $exam->questions_count === 1 ? 'question' : 'questions' }}
                                &middot; {{ $exam->duration_minutes }} min &middot; pass {{ $exam->pass_percentage }}%
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <x-badge :color="$exam->is_published ? 'green' : 'slate'">{{ $exam->is_published ? 'Published' : 'Draft' }}</x-badge>
                            <a href="{{ route('cbt.manage.exams.edit', $exam) }}" class="text-sm font-medium text-brand-600 hover:text-brand-500">Edit</a>
                            <form method="POST" action="{{ route('cbt.manage.exams.destroy', $exam) }}" onsubmit="return confirm('Delete this exam and all its questions?')">
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
