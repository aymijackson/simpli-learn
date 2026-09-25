<x-app-layout title="Library Analytics">
    <x-page-header title="Library analytics" subtitle="Checkout activity and revenue across your catalog.">
        <x-slot:actions>
            <x-button :href="route('library.manage.analytics.export.csv')" variant="secondary">Export CSV</x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-8 grid grid-cols-2 gap-4 sm:grid-cols-4">
        <x-card class="text-center">
            <p class="text-2xl font-bold text-slate-900">{{ $totalCheckouts }}</p>
            <p class="text-xs text-slate-500">Total checkouts</p>
        </x-card>
        <x-card class="text-center">
            <p class="text-2xl font-bold text-slate-900">{{ $activeCount }}</p>
            <p class="text-xs text-slate-500">Currently active</p>
        </x-card>
        <x-card class="text-center">
            <p class="text-2xl font-bold {{ $overdueCount > 0 ? 'text-red-600' : 'text-slate-900' }}">{{ $overdueCount }}</p>
            <p class="text-xs text-slate-500">Overdue</p>
        </x-card>
        <x-card class="text-center">
            <p class="text-2xl font-bold text-slate-900">{{ $avgDurationDays ?? '—' }}</p>
            <p class="text-xs text-slate-500">Avg. days per checkout</p>
        </x-card>
    </div>

    <x-card class="mb-8 text-center">
        <p class="text-2xl font-bold text-slate-900">{{ number_format($totalRevenue, 2) }}</p>
        <p class="text-xs text-slate-500">Total resource purchase revenue</p>
    </x-card>

    <h2 class="mb-3 text-sm font-semibold text-slate-900">Most borrowed</h2>
    @if ($mostBorrowed->isEmpty())
        <x-empty-state title="No checkouts yet" />
    @else
        <x-card :padded="false">
            <ul class="divide-y divide-slate-200">
                @foreach ($mostBorrowed as $resource)
                    <li class="flex items-center justify-between gap-4 px-6 py-4">
                        <div>
                            <p class="text-sm font-medium text-slate-900">{{ $resource->title }}</p>
                            <p class="text-xs text-slate-500">{{ $resource->category }}</p>
                        </div>
                        <x-badge color="indigo">{{ $resource->checkouts_count }} checkouts</x-badge>
                    </li>
                @endforeach
            </ul>
        </x-card>
    @endif
</x-app-layout>
