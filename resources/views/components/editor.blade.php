@props(['name', 'label' => null, 'value' => null, 'id' => null])

@php($fieldId = $id ?? 'editor-'.str_replace(['[', ']', '.'], '-', $name))
@php($tenant = app(\App\Support\Tenancy\Tenancy::class)->current())
@php($uploadUrl = $tenant ? route('tenant.uploads.store', $tenant) : route('admin.uploads.store'))

<div>
    @if ($label)
        <label for="{{ $fieldId }}" class="mb-1.5 block text-sm font-medium text-slate-700">{{ $label }}</label>
    @endif
    <input id="{{ $fieldId }}" type="hidden" name="{{ $name }}" value="{{ $value }}">
    <trix-editor input="{{ $fieldId }}" class="trix-content" data-upload-url="{{ $uploadUrl }}" {{ $attributes }}></trix-editor>
    @error($name)
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
