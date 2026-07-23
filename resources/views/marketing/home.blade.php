<x-marketing-layout :title="$page->title ?? null">
    <section class="bg-slate-50">
        <div class="mx-auto max-w-6xl px-4 py-24 text-center sm:px-6 lg:px-8">
            <h1 class="text-4xl font-bold tracking-tight text-slate-900 sm:text-5xl">
                {{ $page->title ?? 'One platform for learning, testing, and your digital library' }}
            </h1>
            <p class="mx-auto mt-6 max-w-2xl text-lg text-slate-600">
                {{ $page->subtitle ?? 'e-Library brings Learning Management, Computer-Based Testing, and a Digital Library together — use them all, or pick just what your organization needs.' }}
            </p>
            <div class="mt-10 flex items-center justify-center gap-4">
                <x-button :href="route('signup')" class="px-6 py-3 text-base">Create your workspace</x-button>
                <x-button :href="route('login')" variant="secondary" class="px-6 py-3 text-base">Log in</x-button>
            </div>
        </div>
    </section>

    @if ($page?->content)
        <section class="mx-auto max-w-3xl px-4 py-16 sm:px-6 lg:px-8">
            <div class="rich-text text-base text-slate-700">{!! $page->content !!}</div>
        </section>
    @endif

    <section class="mx-auto max-w-6xl px-4 py-16 sm:px-6 lg:px-8">
        <h2 class="text-center text-2xl font-bold tracking-tight text-slate-900">Choose the packages you need</h2>
        <p class="mx-auto mt-3 max-w-xl text-center text-slate-600">Every workspace picks its own combination at signup — use one, two, or all three.</p>

        <div class="mt-12 grid gap-6 sm:grid-cols-3">
            @foreach (\App\Enums\Module::cases() as $module)
                <div class="rounded-2xl border border-slate-200 p-6">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl {{ $module->softClasses() }}">
                        <x-module-icon :module="$module" class="h-6 w-6" />
                    </div>
                    <h3 class="mt-4 text-base font-semibold text-slate-900">{{ $module->label() }}</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ $module->tagline() }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <section class="border-t border-slate-200 bg-slate-50">
        <div class="mx-auto max-w-6xl px-4 py-16 text-center sm:px-6 lg:px-8">
            <h2 class="text-2xl font-bold tracking-tight text-slate-900">Ready to get started?</h2>
            <p class="mt-3 text-slate-600">Sign up in a couple of minutes — your workspace goes live once our team approves it.</p>
            <div class="mt-8">
                <x-button :href="route('signup')" class="px-6 py-3 text-base">Create your workspace</x-button>
            </div>
        </div>
    </section>
</x-marketing-layout>
