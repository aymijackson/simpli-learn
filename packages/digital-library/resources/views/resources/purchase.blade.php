<x-app-layout title="Buy Resource">
    <a href="{{ route('library.resources.show', $resource) }}" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-700">
        &larr; Back to resource
    </a>

    <x-page-header title="Purchase access" :subtitle="$resource->title" />

    <x-card class="mb-6 text-center">
        <p class="text-3xl font-bold text-slate-900">{{ number_format($resource->price, 2) }} {{ $resource->currency }}</p>
        <p class="mt-1 text-sm text-slate-500">Grants the right to borrow this resource.</p>
    </x-card>

    @if ($gateways->isEmpty())
        <x-empty-state title="No payment methods available yet" description="Please check back later or contact the organization." />
    @else
        <x-card>
            <form method="POST" action="{{ route('library.resources.purchase.store', $resource) }}" class="space-y-4">
                @csrf
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Choose a payment method</label>
                @foreach ($gateways as $gateway)
                    <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-slate-200 px-4 py-3 text-sm text-slate-700 transition hover:bg-slate-50 has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50">
                        <input type="radio" name="gateway" value="{{ $gateway->value }}" required class="text-brand-600 focus:ring-brand-600">
                        {{ $gateway->label() }}
                    </label>
                @endforeach
                <x-button type="submit">Continue to payment</x-button>
            </form>
        </x-card>
    @endif
</x-app-layout>
