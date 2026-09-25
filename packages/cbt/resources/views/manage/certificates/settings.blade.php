<x-app-layout title="Certificate Settings">
    <x-page-header title="Certificate settings" subtitle="Defaults applied to every exam unless overridden on the exam itself.">
        <x-slot:actions>
            <x-button :href="route('cbt.manage.payment-gateways.edit')" variant="secondary">Payment gateways</x-button>
            <x-button :href="route('cbt.manage.certificate-payments.index')" variant="secondary">Payments</x-button>
        </x-slot:actions>
    </x-page-header>

    <x-card>
        <form method="POST" action="{{ route('cbt.manage.certificates.settings.update') }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Default certificate policy</label>
                <select name="default_policy" class="block w-full rounded-lg border-0 px-3 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:text-sm">
                    @foreach (['none' => 'No certificate for any exam by default', 'free' => 'Free — issued automatically on passing', 'freemium' => 'Freemium — free certificate, paid verified upgrade', 'paid' => 'Paid — no certificate until payment'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('default_policy', $settings->default_policy) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-input type="number" name="default_price" label="Price (for freemium/paid)" value="{{ old('default_price', $settings->default_price) }}" min="0" step="0.01" />
                <x-input type="text" name="default_currency" label="Currency (3-letter code)" value="{{ old('default_currency', $settings->default_currency) }}" maxlength="3" required />
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Bank transfer instructions</label>
                <textarea name="bank_transfer_instructions" rows="4" class="block w-full rounded-lg border-0 px-3 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:text-sm" placeholder="Bank name, account name, account number, routing/sort code...">{{ old('bank_transfer_instructions', $settings->bank_transfer_instructions) }}</textarea>
                <p class="mt-1 text-xs text-slate-500">Shown to learners who choose to pay by bank transfer.</p>
            </div>

            <div class="grid gap-5 sm:grid-cols-3">
                <x-input type="text" name="issuer_name" label="Issuer name (optional)" value="{{ old('issuer_name', $settings->issuer_name) }}" />
                <x-input type="text" name="signatory_name" label="Signatory name (optional)" value="{{ old('signatory_name', $settings->signatory_name) }}" />
                <x-input type="text" name="signatory_title" label="Signatory title (optional)" value="{{ old('signatory_title', $settings->signatory_title) }}" />
            </div>

            <x-button type="submit">Save settings</x-button>
        </form>
    </x-card>
</x-app-layout>
