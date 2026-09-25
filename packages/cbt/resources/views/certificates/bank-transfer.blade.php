<x-app-layout title="Bank Transfer">
    <a href="{{ route('cbt.attempts.result', $attempt) }}" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-700">
        &larr; Back to results
    </a>

    <x-page-header title="Pay by bank transfer" />

    <x-card class="mb-6">
        <p class="text-sm font-medium text-slate-500">Amount</p>
        <p class="text-2xl font-bold text-slate-900">{{ number_format($payment->amount, 2) }} {{ $payment->currency }}</p>

        <p class="mt-4 text-sm font-medium text-slate-500">Payment reference</p>
        <p class="font-mono text-sm text-slate-900">{{ $payment->reference }}</p>
        <p class="mt-1 text-xs text-slate-500">Please include this reference with your transfer so it can be matched to your payment.</p>

        @if ($settings->bank_transfer_instructions)
            <div class="mt-4 rounded-lg bg-slate-50 p-4 text-sm text-slate-700 whitespace-pre-line">{{ $settings->bank_transfer_instructions }}</div>
        @endif
    </x-card>

    @if ($payment->isPending())
        <form method="POST" action="{{ route('cbt.attempts.certificate-payment.bank-transfer.report', [$attempt, $payment]) }}">
            @csrf
            <x-button type="submit">I've sent the transfer</x-button>
        </form>
    @else
        <p class="text-sm text-slate-500">Payment status: {{ $payment->status->value }}</p>
    @endif
</x-app-layout>
