<x-app-layout title="Payment Gateways">
    <x-page-header title="Payment gateways" subtitle="Configure how learners pay for verified certificates." />

    @if ($platformManaged)
        <div class="mb-6 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-700 ring-1 ring-inset ring-amber-600/20">
            The platform currently collects all certificate payments centrally. Your own gateway credentials below are saved but not used while this is active.
        </div>
    @endif

    <div class="space-y-6">
        @foreach ($gateways as $gateway)
            @php($credential = $credentials[$gateway->value])
            <x-card>
                <form method="POST" action="{{ route('cbt.manage.payment-gateways.update', $gateway->value) }}" class="space-y-4">
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
                        <p class="text-xs text-slate-500">Bank account details are configured on the <a href="{{ route('cbt.manage.certificates.settings.edit') }}" class="underline">certificate settings</a> page.</p>
                    @endif

                    <x-button type="submit" variant="secondary">Save</x-button>
                </form>
            </x-card>
        @endforeach
    </div>
</x-app-layout>
