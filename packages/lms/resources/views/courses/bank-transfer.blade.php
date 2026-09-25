<x-app-layout title="Bank Transfer">
    <a href="{{ route('lms.courses.show', $course) }}" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-700">
        &larr; Back to course
    </a>

    <x-page-header title="Pay by bank transfer" />

    <x-card class="mb-6">
        <p class="text-sm font-medium text-slate-500">Amount</p>
        <p class="text-2xl font-bold text-slate-900">{{ number_format($purchase->amount, 2) }} {{ $purchase->currency }}</p>

        <p class="mt-4 text-sm font-medium text-slate-500">Payment reference</p>
        <p class="font-mono text-sm text-slate-900">{{ $purchase->reference }}</p>
        <p class="mt-1 text-xs text-slate-500">Please include this reference with your transfer so it can be matched to your payment.</p>

        @if ($bankInstructions)
            <div class="mt-4 rounded-lg bg-slate-50 p-4 text-sm text-slate-700 whitespace-pre-line">{{ $bankInstructions }}</div>
        @endif
    </x-card>

    @if ($purchase->isPending())
        <form method="POST" action="{{ route('lms.courses.purchase.bank-transfer.report', [$course, $purchase]) }}">
            @csrf
            <x-button type="submit">I've sent the transfer</x-button>
        </form>
    @else
        <p class="text-sm text-slate-500">Payment status: {{ $purchase->status->value }}</p>
    @endif
</x-app-layout>
