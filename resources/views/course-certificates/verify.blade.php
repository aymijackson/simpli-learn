<x-marketing-layout title="Verify Course Certificate">
    <section class="mx-auto max-w-2xl px-4 py-16 sm:px-6 lg:px-8">
        <div class="rounded-2xl bg-white p-8 text-center ring-1 ring-slate-200 shadow-sm">
            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-3 py-1 text-sm font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                &check; Verified certificate
            </span>

            <h1 class="mt-4 text-2xl font-bold text-slate-900">{{ $certificate->user->name }}</h1>
            <p class="mt-1 text-base text-slate-600">completed {{ $certificate->course->title }}</p>
            <p class="mt-4 text-sm text-slate-500">Issued {{ $certificate->issued_at->format('F j, Y') }}</p>

            <p class="mt-6 text-xs text-slate-400">Certificate #{{ $certificate->id }} &middot; {{ $certificate->verification_token }}</p>
        </div>
    </section>
</x-marketing-layout>
