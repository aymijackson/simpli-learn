<x-app-layout :title="__('My Checkouts')">
    <x-page-header :title="__('My checkouts')" :subtitle="__('Borrowed resources and waitlist positions.')" />

    @if ($holds->isNotEmpty())
        <h2 class="mb-3 text-sm font-semibold text-slate-900">{{ __('Waitlist') }}</h2>
        <div class="mb-8 space-y-3">
            @foreach ($holds as $hold)
                <x-card class="flex items-center justify-between gap-4">
                    <div>
                        <a href="{{ route('library.resources.show', $hold->resource) }}" class="text-sm font-medium text-slate-900 hover:text-brand-600">{{ $hold->resource->title }}</a>
                        <p class="text-xs text-slate-500">
                            @if ($hold->hasLiveOffer())
                                {{ __('Your turn! Claim by :expires_at', ['expires_at' => $hold->expires_at->translatedFormat('M j, Y')]) }}
                            @else
                                {{ __('#:position in line', ['position' => $hold->position()]) }}
                            @endif
                        </p>
                    </div>
                    @if ($hold->hasLiveOffer())
                        <form method="POST" action="{{ route('library.holds.claim', $hold) }}">
                            @csrf
                            <x-button type="submit">{{ __('Claim') }}</x-button>
                        </form>
                    @endif
                </x-card>
            @endforeach
        </div>
    @endif

    <h2 class="mb-3 text-sm font-semibold text-slate-900">{{ __('Checkouts') }}</h2>
    @if ($checkouts->isEmpty())
        <x-empty-state :title="__('No checkouts yet')" />
    @else
        <div class="space-y-3">
            @foreach ($checkouts as $checkout)
                <x-card class="flex items-center justify-between gap-4">
                    <div>
                        <a href="{{ route('library.resources.show', $checkout->resource) }}" class="text-sm font-medium text-slate-900 hover:text-brand-600">{{ $checkout->resource->title }}</a>
                        <p class="text-xs text-slate-500">
                            @if ($checkout->isActive())
                                {{ __('Due :due_at', ['due_at' => $checkout->due_at->translatedFormat('M j, Y')]) }}
                                @if ($checkout->isOverdue())
                                    <span class="text-amber-700">{{ __('· loan ended, returning automatically') }}</span>
                                @endif
                            @else
                                {{ __('Returned :returned_at', ['returned_at' => $checkout->returned_at->translatedFormat('M j, Y')]) }}
                            @endif
                        </p>
                    </div>
                    @if ($checkout->isActive())
                        <form method="POST" action="{{ route('library.checkouts.return', $checkout) }}">
                            @csrf
                            <x-button type="submit" variant="secondary">{{ __('Return') }}</x-button>
                        </form>
                    @endif
                </x-card>
            @endforeach
        </div>
    @endif
</x-app-layout>
