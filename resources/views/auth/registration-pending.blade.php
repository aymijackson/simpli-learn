<x-guest-layout title="Registration received">
    <x-card class="text-center">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-6 w-6">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
            </svg>
        </div>

        <h1 class="mt-4 text-lg font-semibold text-slate-900">
            @if ($organization)
                Thanks for signing up, {{ $organization }}!
            @else
                Thanks for signing up!
            @endif
        </h1>
        <p class="mt-2 text-sm text-slate-500">
            Your workspace is pending approval. We'll let you know as soon as it's ready — you'll then be able to log in from your workspace's own sign-in page.
        </p>

        <div class="mt-6">
            <x-button :href="route('home')" variant="secondary">Back to home</x-button>
        </div>
    </x-card>
</x-guest-layout>
