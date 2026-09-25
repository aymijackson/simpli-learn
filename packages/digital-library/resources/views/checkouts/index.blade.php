<x-app-layout title="My Checkouts">
    <x-page-header title="My checkouts" subtitle="Borrowed resources and waitlist positions." />

    @if ($holds->isNotEmpty())
        <h2 class="mb-3 text-sm font-semibold text-slate-900">Waitlist</h2>
        <div class="mb-8 space-y-3">
            @foreach ($holds as $hold)
                <x-card class="flex items-center justify-between gap-4">
                    <div>
                        <a href="{{ route('library.resources.show', $hold->resource) }}" class="text-sm font-medium text-slate-900 hover:text-brand-600">{{ $hold->resource->title }}</a>
                        <p class="text-xs text-slate-500">
                            @if ($hold->hasLiveOffer())
                                Your turn! Claim by {{ $hold->expires_at->format('M j, Y') }}
                            @else
                                #{{ $hold->position() }} in line
                            @endif
                        </p>
                    </div>
                    @if ($hold->hasLiveOffer())
                        <form method="POST" action="{{ route('library.holds.claim', $hold) }}">
                            @csrf
                            <x-button type="submit">Claim</x-button>
                        </form>
                    @endif
                </x-card>
            @endforeach
        </div>
    @endif

    <h2 class="mb-3 text-sm font-semibold text-slate-900">Checkouts</h2>
    @if ($checkouts->isEmpty())
        <x-empty-state title="No checkouts yet" />
    @else
        <div class="space-y-3">
            @foreach ($checkouts as $checkout)
                <x-card class="flex items-center justify-between gap-4">
                    <div>
                        <a href="{{ route('library.resources.show', $checkout->resource) }}" class="text-sm font-medium text-slate-900 hover:text-brand-600">{{ $checkout->resource->title }}</a>
                        <p class="text-xs text-slate-500">
                            @if ($checkout->isActive())
                                Due {{ $checkout->due_at->format('M j, Y') }}
                                @if ($checkout->isOverdue())
                                    <span class="text-red-600">&middot; overdue</span>
                                @endif
                            @else
                                Returned {{ $checkout->returned_at->format('M j, Y') }}
                            @endif
                        </p>
                    </div>
                    @if ($checkout->isActive())
                        <form method="POST" action="{{ route('library.checkouts.return', $checkout) }}">
                            @csrf
                            <x-button type="submit" variant="secondary">Return</x-button>
                        </form>
                    @endif
                </x-card>
            @endforeach
        </div>
    @endif
</x-app-layout>
