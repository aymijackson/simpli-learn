<x-admin-layout title="Payment Settings">
    <x-page-header title="Payment settings" subtitle="Control whether tenants manage their own certificate payments or the platform collects centrally." />

    <x-card class="mb-8">
        <form method="POST" action="{{ route('admin.payment-settings.update') }}" class="space-y-4">
            @csrf
            @method('PUT')
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Mode</label>
            @foreach ($modes as $mode)
                <label class="flex items-start gap-3 rounded-lg border border-slate-200 p-3">
                    <input type="radio" name="mode" value="{{ $mode->value }}" class="mt-0.5 text-brand-600 focus:ring-brand-600" @checked($settings->mode === $mode)>
                    <span class="text-sm text-slate-700">{{ $mode->label() }}</span>
                </label>
            @endforeach
            <x-button type="submit">Save mode</x-button>
        </form>
    </x-card>

    <h2 class="mb-4 text-sm font-semibold text-slate-900">Platform gateway credentials</h2>
    <p class="mb-4 text-sm text-slate-500">Used for every tenant's payments while platform-managed mode is active.</p>
    <div class="mb-8 space-y-6">
        @foreach ($gateways as $gateway)
            @php($credential = $credentials[$gateway->value])
            <x-card>
                <form method="POST" action="{{ route('admin.payment-settings.gateways.update', $gateway->value) }}" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-slate-900">{{ $gateway->label() }}</h3>
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
                        <x-input type="text" name="public_key" label="Bank account details (shown to learners)" value="{{ $credential?->credentials['public_key'] ?? '' }}" />
                    @endif
                    <x-button type="submit" variant="secondary">Save</x-button>
                </form>
            </x-card>
        @endforeach
    </div>

    <h2 class="mb-4 text-sm font-semibold text-slate-900">Per-tenant revenue split</h2>
    <p class="mb-4 text-sm text-slate-500">Only relevant in platform-managed mode — how much of each tenant's certificate revenue you record as your fee. No money is transferred automatically; this is for your own manual accounting.</p>
    <x-card :padded="false">
        <ul class="divide-y divide-slate-200">
            @foreach ($tenants as $workspace)
                @php($split = $workspace->revenueSplit)
                <li class="px-6 py-4">
                    <form method="POST" action="{{ route('admin.tenants.revenue-split', $workspace) }}" class="grid gap-3 sm:grid-cols-[1fr_1fr_1fr_1fr_auto] sm:items-end">
                        @csrf
                        @method('PUT')
                        <div>
                            <p class="text-sm font-medium text-slate-900">{{ $workspace->name }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-700">Split type</label>
                            <select name="split_type" class="block w-full rounded-lg border-0 px-3 py-2 text-sm text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600">
                                @foreach (\App\Enums\RevenueSplitType::cases() as $type)
                                    <option value="{{ $type->value }}" @selected(($split->split_type ?? \App\Enums\RevenueSplitType::Manual) === $type)>{{ $type->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <x-input type="number" name="percentage" label="Percentage" value="{{ $split->percentage ?? '' }}" min="0" max="100" step="0.01" />
                        <x-input type="number" name="flat_fee" label="Flat fee" value="{{ $split->flat_fee ?? '' }}" min="0" step="0.01" />
                        <x-button type="submit" variant="secondary">Save</x-button>
                    </form>
                </li>
            @endforeach
        </ul>
    </x-card>
</x-admin-layout>
