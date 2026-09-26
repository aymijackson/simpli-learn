@props(['value' => 0, 'size' => 'h-4 w-4'])

{{-- Read-only 0–5 star display with half stars, e.g. <x-stars :value="4.5" />. --}}
@php($value = max(0, min(5, (float) $value)))
<span {{ $attributes->merge(['class' => 'inline-flex items-center text-amber-400']) }} role="img" aria-label="{{ number_format($value, 1) }} out of 5 stars">
    @for ($i = 1; $i <= 5; $i++)
        @php($fill = $value >= $i ? 100 : ($value > $i - 1 ? (int) round(($value - $i + 1) * 100) : 0))
        <span class="relative inline-block {{ $size }}">
            <svg viewBox="0 0 20 20" class="absolute inset-0 {{ $size }} text-slate-200" fill="currentColor" aria-hidden="true"><path d="M10 1.5l2.6 5.3 5.9.9-4.25 4.1 1 5.8L10 14.9l-5.25 2.7 1-5.8L1.5 7.7l5.9-.9L10 1.5z"/></svg>
            <span class="absolute inset-0 overflow-hidden" style="width: {{ $fill }}%">
                <svg viewBox="0 0 20 20" class="{{ $size }}" fill="currentColor" aria-hidden="true"><path d="M10 1.5l2.6 5.3 5.9.9-4.25 4.1 1 5.8L10 14.9l-5.25 2.7 1-5.8L1.5 7.7l5.9-.9L10 1.5z"/></svg>
            </span>
        </span>
    @endfor
</span>
