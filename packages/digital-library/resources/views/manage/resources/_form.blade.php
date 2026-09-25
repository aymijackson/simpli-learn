<x-input type="text" name="title" id="resource-title" label="Title" value="{{ old('title', $resource->title ?? '') }}" required autofocus />

<x-input type="text" name="slug" id="resource-slug" label="Slug" value="{{ old('slug', $resource->slug ?? '') }}" required />

<div class="grid gap-5 sm:grid-cols-2">
    <x-input type="text" name="author" label="Author (optional)" value="{{ old('author', $resource->author ?? '') }}" />
    <x-input type="text" name="category" label="Category" value="{{ old('category', $resource->category ?? '') }}" required />
</div>

<x-editor name="description" label="Description (optional)" :value="old('description', $resource->description ?? '')" />

<x-input type="url" name="external_url" label="External URL (optional)" value="{{ old('external_url', $resource->external_url ?? '') }}" placeholder="https://" />

<div class="grid gap-5 sm:grid-cols-2">
    <x-input type="text" name="isbn" label="ISBN (optional)" value="{{ old('isbn', $resource->isbn ?? '') }}" />
    <x-input type="text" name="publisher" label="Publisher (optional)" value="{{ old('publisher', $resource->publisher ?? '') }}" />
</div>

<div class="grid gap-5 sm:grid-cols-2">
    <x-input type="number" name="publication_year" label="Publication year (optional)" value="{{ old('publication_year', $resource->publication_year ?? '') }}" min="1000" />
    <x-input type="text" name="language" label="Language (optional)" value="{{ old('language', $resource->language ?? '') }}" />
</div>

<x-input type="text" name="tags" label="Tags (optional, comma-separated)" value="{{ old('tags', $tagNames ?? '') }}" placeholder="algebra, revision, past-papers" />

<div>
    <label class="mb-1.5 block text-sm font-medium text-slate-700">Cover image (optional)</label>
    @if (! empty($resource->cover_image_path))
        <img src="{{ Illuminate\Support\Facades\Storage::disk('public')->url($resource->cover_image_path) }}" alt="" class="mb-2 h-24 w-auto rounded-lg ring-1 ring-slate-200">
    @endif
    <input type="file" name="cover_image" accept="image/*" class="block w-full text-sm text-slate-900 file:mr-4 file:rounded-lg file:border-0 file:bg-slate-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-100">
</div>

<label class="flex items-center gap-2 text-sm text-slate-600">
    <input type="checkbox" name="is_published" value="1" class="rounded border-slate-300 text-brand-600 focus:ring-brand-600" @checked(old('is_published', $resource->is_published ?? false))>
    Published (visible to members)
</label>

<div>
    <label class="flex items-center gap-2 text-sm text-slate-600">
        <input id="requires-checkout" type="checkbox" name="requires_checkout" value="1" class="rounded border-slate-300 text-brand-600 focus:ring-brand-600" @checked(old('requires_checkout', $resource->requires_checkout ?? false))>
        Requires checkout (borrowing) to access hosted files
    </label>
    <p class="mt-1 text-xs text-slate-500">When off, any tenant member can view this resource's files freely. When on, files are only accessible with an active checkout.</p>
</div>

<div id="checkout-fields" class="grid gap-5 sm:grid-cols-2 {{ old('requires_checkout', $resource->requires_checkout ?? false) ? '' : 'hidden' }}">
    <x-input type="number" name="total_copies" label="Total copies (blank = unlimited)" value="{{ old('total_copies', $resource->total_copies ?? '') }}" min="1" />
    <x-input type="number" name="checkout_duration_days" label="Checkout duration (days)" value="{{ old('checkout_duration_days', $resource->checkout_duration_days ?? 14) }}" min="1" />
</div>

@php($currentPricingPolicy = old('pricing_policy', $resource->pricing_policy?->value ?? 'free'))

<div>
    <label class="mb-1.5 block text-sm font-medium text-slate-700">Pricing</label>
    <select id="pricing-policy" name="pricing_policy" class="block w-full rounded-lg border-0 px-3 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:text-sm">
        @foreach (\Elibrary\Library\Enums\LibraryPricingPolicy::cases() as $policy)
            <option value="{{ $policy->value }}" @selected($currentPricingPolicy === $policy->value)>{{ $policy->label() }}</option>
        @endforeach
    </select>
    <p class="mt-1 text-xs text-slate-500">A purchase grants the right to borrow — it doesn't bypass copy availability above.</p>
</div>

<div id="resource-price-field" class="grid gap-5 sm:grid-cols-2 {{ $currentPricingPolicy === 'free' ? 'hidden' : '' }}">
    <x-input type="number" name="price" label="Price" value="{{ old('price', $resource->price ?? '') }}" min="0" step="0.01" />
    <x-input type="text" name="currency" label="Currency (3-letter code)" value="{{ old('currency', $resource->currency ?? '') }}" maxlength="3" />
</div>

@push('scripts')
    <script>
        (function () {
            const titleField = document.getElementById('resource-title');
            const slugField = document.getElementById('resource-slug');
            let slugTouched = slugField.value.length > 0;

            const slugify = (value) => value.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');

            titleField.addEventListener('input', () => {
                if (!slugTouched) {
                    slugField.value = slugify(titleField.value);
                }
            });

            slugField.addEventListener('input', () => {
                slugTouched = true;
            });

            const requiresCheckoutField = document.getElementById('requires-checkout');
            const checkoutFields = document.getElementById('checkout-fields');
            requiresCheckoutField.addEventListener('change', () => {
                checkoutFields.classList.toggle('hidden', !requiresCheckoutField.checked);
            });

            const pricingPolicyField = document.getElementById('pricing-policy');
            const resourcePriceField = document.getElementById('resource-price-field');
            pricingPolicyField.addEventListener('change', () => {
                resourcePriceField.classList.toggle('hidden', pricingPolicyField.value === 'free');
            });
        })();
    </script>
@endpush
