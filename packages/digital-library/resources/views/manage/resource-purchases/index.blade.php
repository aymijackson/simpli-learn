<x-app-layout title="Resource Purchases">
    <x-page-header title="Resource purchases" subtitle="Confirm bank-transfer payments and review purchase history." />

    @if ($purchases->isEmpty())
        <x-empty-state title="No resource purchases yet" />
    @else
        <x-card :padded="false">
            <ul class="divide-y divide-slate-200">
                @foreach ($purchases as $purchase)
                    <li class="flex items-center justify-between gap-4 px-6 py-4">
                        <div>
                            <p class="text-sm font-medium text-slate-900">{{ $purchase->user->name }} &middot; {{ $purchase->resource->title }}</p>
                            <p class="text-xs text-slate-500">
                                {{ $purchase->gateway->label() }} &middot; {{ number_format($purchase->amount, 2) }} {{ $purchase->currency }}
                                &middot; ref {{ $purchase->reference }}
                                &middot; {{ $purchase->created_at->format('M j, Y g:ia') }}
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <x-badge :color="match($purchase->status->value) { 'paid' => 'green', 'cancelled', 'failed' => 'red', default => 'amber' }">
                                {{ ucfirst($purchase->status->value) }}
                            </x-badge>
                            @if ($purchase->isPending())
                                <form method="POST" action="{{ route('library.manage.resource-purchases.confirm', $purchase) }}">
                                    @csrf
                                    <button type="submit" class="text-sm font-medium text-emerald-600 hover:text-emerald-500">Confirm</button>
                                </form>
                                <form method="POST" action="{{ route('library.manage.resource-purchases.reject', $purchase) }}">
                                    @csrf
                                    <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-500">Reject</button>
                                </form>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        </x-card>

        <div class="mt-4">
            {{ $purchases->links() }}
        </div>
    @endif
</x-app-layout>
