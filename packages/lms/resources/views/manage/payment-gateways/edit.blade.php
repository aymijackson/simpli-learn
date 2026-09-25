<x-app-layout title="Course Payment Gateways">
    <x-page-header title="Course payment gateways" subtitle="Configure how learners pay for paid courses." />

    <div class="space-y-6">
        @foreach ($gateways as $gateway)
            @php($credential = $credentials[$gateway->value])
            <x-card>
                <form method="POST" action="{{ route('lms.manage.payment-gateways.update', $gateway->value) }}" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-slate-900">{{ $gateway->label() }}</h2>
                        <label class="flex items-center gap-2 text-sm text-slate-600">
                            <input type="checkbox" name="is_enabled" value="1" class="rounded border-slate-300 text-brand-600 focus:ring-brand-600" @checked($credential?->is_enabled)>
                            Enabled
                        </label>
                    </div>

                    @if ($gateway->value !== 'bank_transfer')
                        <div class="grid gap-4 sm:grid-cols-3">
                            <x-input type="text" name="public_key" label="Public key" value="{{ $credential?->credentials['public_key'] ?? '' }}" />
                            <x-input type="password" name="secret_key" label="Secret key" value="{{ $credential?->credentials['secret_key'] ?? '' }}" />
                            <x-input type="password" name="webhook_secret" label="Webhook secret" value="{{ $credential?->credentials['webhook_secret'] ?? '' }}" />
                        </div>
                    @else
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Bank account details (shown to learners)</label>
                            <textarea name="public_key" rows="4" class="block w-full rounded-lg border-0 px-3 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:text-sm" placeholder="Bank name, account name, account number, routing/sort code...">{{ $credential?->credentials['public_key'] ?? '' }}</textarea>
                        </div>
                    @endif

                    <x-button type="submit" variant="secondary">Save</x-button>
                </form>
            </x-card>
        @endforeach
    </div>
</x-app-layout>
