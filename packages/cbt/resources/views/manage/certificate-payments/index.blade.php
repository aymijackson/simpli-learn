<x-app-layout title="Certificate Payments">
    <x-page-header title="Certificate payments" subtitle="Confirm bank-transfer payments and review payment history." />

    @if ($payments->isEmpty())
        <x-empty-state title="No certificate payments yet" />
    @else
        <x-card :padded="false">
            <ul class="divide-y divide-slate-200">
                @foreach ($payments as $payment)
                    <li class="flex items-center justify-between gap-4 px-6 py-4">
                        <div>
                            <p class="text-sm font-medium text-slate-900">{{ $payment->user->name }} &middot; {{ $payment->attempt->exam->title }}</p>
                            <p class="text-xs text-slate-500">
                                {{ $payment->gateway->label() }} &middot; {{ number_format($payment->amount, 2) }} {{ $payment->currency }}
                                &middot; ref {{ $payment->reference }}
                                &middot; {{ $payment->created_at->format('M j, Y g:ia') }}
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <x-badge :color="match($payment->status->value) { 'paid' => 'green', 'cancelled', 'failed' => 'red', default => 'amber' }">
                                {{ ucfirst($payment->status->value) }}
                            </x-badge>
                            @if ($payment->isPending())
                                <form method="POST" action="{{ route('cbt.manage.certificate-payments.confirm', $payment) }}">
                                    @csrf
                                    <button type="submit" class="text-sm font-medium text-emerald-600 hover:text-emerald-500">Confirm</button>
                                </form>
                                <form method="POST" action="{{ route('cbt.manage.certificate-payments.reject', $payment) }}">
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
            {{ $payments->links() }}
        </div>
    @endif
</x-app-layout>
