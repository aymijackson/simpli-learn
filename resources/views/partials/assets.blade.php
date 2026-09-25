{{-- Shared <head> assets for every layout. No build step: Tailwind generates
     styles in the browser from the classes on the page, and third-party
     libraries load from jsDelivr. --}}
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
<style type="text/tailwindcss">
    @theme {
        --font-sans: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji',
            'Segoe UI Symbol', 'Noto Color Emoji';

        --color-brand-50: #f5f3ff;
        --color-brand-100: #ede9fe;
        --color-brand-200: #ddd6fe;
        --color-brand-300: #c4b5fd;
        --color-brand-400: #a78bfa;
        --color-brand-500: #8b5cf6;
        --color-brand-600: #7c3aed;
        --color-brand-700: #6d28d9;
        --color-brand-800: #5b21b6;
        --color-brand-900: #4c1d95;
    }
</style>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/trix@2.1.19/dist/trix.css">
<link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
{{-- app.js registers Trix hooks, so it must run before Trix upgrades the editors. --}}
<script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}" defer></script>
<script src="https://cdn.jsdelivr.net/npm/trix@2.1.19/dist/trix.umd.min.js" defer></script>
