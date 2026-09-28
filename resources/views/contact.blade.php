<x-marketing-layout title="Contact us">
    <section class="mx-auto max-w-2xl px-4 py-16 sm:px-6 lg:py-20">
        <h1 class="font-display text-4xl font-bold tracking-tight text-slate-900">Contact us</h1>
        <p class="mt-3 text-base text-slate-600">Questions about e-Library, a demo, or help with your workspace? Send us a message and we'll reply by email.</p>

        @if (session('status'))
            <div role="status" class="mt-8 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 ring-1 ring-emerald-200">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('contact.store') }}" class="mt-8 space-y-5 rounded-2xl bg-white p-6 ring-1 ring-slate-200 sm:p-8" novalidate>
            @csrf
            <input type="hidden" name="form_token" value="{{ $formToken }}">

            {{-- Honeypot: hidden from people and screen readers; bots fill it in. --}}
            <div class="absolute -left-[9999px] h-px w-px overflow-hidden" aria-hidden="true">
                <label for="contact-website">Leave this field empty</label>
                <input id="contact-website" type="text" name="website" tabindex="-1" autocomplete="off">
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-input name="name" label="Your name" value="{{ old('name') }}" required maxlength="100" autocomplete="name" />
                <x-input type="email" name="email" label="Email" value="{{ old('email') }}" required maxlength="190" autocomplete="email" />
            </div>
            <x-input name="subject" label="Subject (optional)" value="{{ old('subject') }}" maxlength="150" />
            <x-textarea name="message" label="Message" rows="6" required minlength="10" maxlength="5000">{{ old('message') }}</x-textarea>

            @if ($turnstileSiteKey)
                <div class="cf-turnstile" data-sitekey="{{ $turnstileSiteKey }}"></div>
                @push('scripts')
                    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
                @endpush
            @endif

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs text-slate-500">We use your details only to reply, keep them encrypted, and delete them after a year.</p>
                <x-button type="submit" icon-right="arrow-right" class="shrink-0 whitespace-nowrap">Send message</x-button>
            </div>
        </form>
    </section>
</x-marketing-layout>
