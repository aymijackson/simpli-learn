<x-app-layout :title="$tenant->name">
    <x-page-header title="Welcome back" :subtitle="$tenant->name.'’s workspace'" />

    @if ($enabledModules->isEmpty())
        <x-empty-state
            title="No modules are enabled yet"
            description="Contact your administrator to enable Learning, CBT, or Library access for this organization."
        />
    @else
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($enabledModules as $tenantModule)
                @php($module = $tenantModule->module)
                <a href="{{ route($module->routeName()) }}" class="group block">
                    <x-card class="h-full transition hover:shadow-md hover:ring-slate-300">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl {{ $module->softClasses() }}">
                            <x-module-icon :module="$module" class="h-6 w-6" />
                        </div>
                        <h3 class="mt-4 text-base font-semibold text-slate-900">{{ $module->label() }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ $module->tagline() }}</p>
                        <span class="mt-4 inline-flex items-center gap-1 text-sm font-medium {{ $module->textClasses() }}">
                            Open
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4 transition group-hover:translate-x-0.5">
                                <path fill-rule="evenodd" d="M12.293 4.293a1 1 0 011.414 0l5 5a1 1 0 010 1.414l-5 5a1 1 0 01-1.414-1.414L15.586 11H3a1 1 0 110-2h12.586l-3.293-3.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </span>
                    </x-card>
                </a>
            @endforeach
        </div>
    @endif
</x-app-layout>
