<x-app-layout title="Checkouts">
    <a href="{{ route('library.manage.resources.edit', $resource) }}" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-700">
        &larr; {{ $resource->title }}
    </a>

    <x-page-header title="Checkouts &amp; waitlist" :subtitle="$resource->title" />

    <div class="mb-8 text-sm text-slate-600">
        {{ $resource->activeCheckoutsCount() }} checked out
        @if ($resource->total_copies !== null)
            of {{ $resource->total_copies }} copies &middot; {{ $resource->availableCopies() }} available
        @else
            &middot; unlimited copies
        @endif
    </div>

    <h2 class="mb-3 text-sm font-semibold text-slate-900">Active checkouts</h2>
    @if ($activeCheckouts->isEmpty())
        <x-empty-state title="No active checkouts" />
    @else
        <x-card :padded="false" class="mb-8">
            <ul class="divide-y divide-slate-200">
                @foreach ($activeCheckouts as $checkout)
                    <li class="flex items-center justify-between gap-4 px-6 py-4">
                        <div>
                            <p class="text-sm font-medium text-slate-900">{{ $checkout->user->name }}</p>
                            <p class="text-xs text-slate-500">
                                Due {{ $checkout->due_at->format('M j, Y') }}
                                @if ($checkout->isOverdue())
                                    <span class="text-red-600">&middot; overdue</span>
                                @endif
                            </p>
                        </div>
                        <form method="POST" action="{{ route('library.manage.resources.checkouts.force-return', [$resource, $checkout]) }}">
                            @csrf
                            <button type="submit" class="text-sm font-medium text-brand-600 hover:text-brand-500">Mark returned</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        </x-card>
    @endif

    <h2 class="mb-3 text-sm font-semibold text-slate-900">Waitlist</h2>
    @if ($holds->isEmpty())
        <x-empty-state title="No one is waiting" />
    @else
        <x-card :padded="false">
            <ul class="divide-y divide-slate-200">
                @foreach ($holds as $hold)
                    <li class="flex items-center justify-between gap-4 px-6 py-4">
                        <p class="text-sm font-medium text-slate-900">{{ $hold->user->name }}</p>
                        <p class="text-xs text-slate-500">
                            #{{ $hold->position() }}
                            @if ($hold->hasLiveOffer())
                                &middot; notified, expires {{ $hold->expires_at->format('M j, Y') }}
                            @endif
                        </p>
                    </li>
                @endforeach
            </ul>
        </x-card>
    @endif
</x-app-layout>
