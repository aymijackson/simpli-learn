@php
    $modules = [
        [
            'id' => 'lms', 'icon' => 'academic-cap', 'eyebrow' => 'Learning management', 'accent' => 'indigo',
            'title' => 'Courses people actually finish',
            'body' => 'Build structured courses from modules and lessons, drip content behind assessments, and let learners pick up exactly where they left off.',
            'points' => ['Modules, lessons, rich text, video, audio and downloads', 'Free previews, paid courses and completion certificates', 'Progress tracking and gated lessons that unlock as learners pass'],
        ],
        [
            'id' => 'cbt', 'icon' => 'clipboard-check', 'eyebrow' => 'Computer-based testing', 'accent' => 'emerald',
            'title' => 'Exams that are fair, timed and instantly graded',
            'body' => 'Run anything from a five-question quiz to a high-stakes certification exam, with question banks, sections and results the moment learners submit.',
            'points' => ['Randomised question banks, sections and multi-select answers', 'Time limits, retake rules and availability windows', 'Integrity monitoring, analytics and verifiable certificates'],
        ],
        [
            'id' => 'library', 'icon' => 'book-open', 'eyebrow' => 'Digital library', 'accent' => 'amber',
            'title' => 'A library that\'s open around the clock',
            'body' => 'Publish books, papers and study material with a searchable catalog, an in-browser reader, and lending rules that mirror your physical shelves.',
            'points' => ['Read PDF and EPUB right in the browser', 'Borrowing, waitlists, due dates and favourites', 'Ratings, categories, topics and engagement analytics'],
        ],
    ];
    $accents = [
        'indigo' => ['soft' => 'bg-indigo-50 text-indigo-700', 'text' => 'text-indigo-600', 'from' => 'from-indigo-500', 'to' => 'to-violet-700'],
        'emerald' => ['soft' => 'bg-emerald-50 text-emerald-700', 'text' => 'text-emerald-600', 'from' => 'from-emerald-500', 'to' => 'to-teal-700'],
        'amber' => ['soft' => 'bg-amber-50 text-amber-800', 'text' => 'text-amber-600', 'from' => 'from-amber-500', 'to' => 'to-orange-600'],
    ];
    $features = [
        ['icon' => 'trophy', 'title' => 'Verifiable certificates', 'body' => 'Every certificate carries a public verification link employers can check.'],
        ['icon' => 'credit-card', 'title' => 'Built-in payments', 'body' => 'Sell courses, certificates and books via Stripe, Paystack, Flutterwave or bank transfer.'],
        ['icon' => 'shield-check', 'title' => 'Exam integrity', 'body' => 'Flag tab switches and copy attempts so results stay trustworthy.'],
        ['icon' => 'chart-bar', 'title' => 'Analytics that matter', 'body' => 'Scores, pass rates, reading activity and exports for every exam and resource.'],
        ['icon' => 'lock', 'title' => 'Protected content', 'body' => 'Stream videos and documents securely instead of handing out raw files.'],
        ['icon' => 'device', 'title' => 'Works on any device', 'body' => 'Learners study, test and read on phones, tablets and laptops alike.'],
        ['icon' => 'users', 'title' => 'Your own workspace', 'body' => 'Each organization gets a private space for its people, content and settings.'],
        ['icon' => 'stack', 'title' => 'Pick what you need', 'body' => 'Switch on one module or all three — and add more whenever you\'re ready.'],
    ];
    $audiences = [
        ['icon' => 'academic-cap', 'label' => 'Universities'],
        ['icon' => 'building', 'label' => 'Schools'],
        ['icon' => 'light-bulb', 'label' => 'Training centres'],
        ['icon' => 'clipboard-check', 'label' => 'Exam bodies'],
        ['icon' => 'book-open', 'label' => 'Libraries'],
        ['icon' => 'users', 'label' => 'Corporate academies'],
    ];
@endphp

<x-marketing-layout :title="$page->title ?? null">
    {{-- Hero --}}
    <section class="relative isolate overflow-hidden bg-ink-950">
        <div class="bg-dots absolute inset-0 -z-10"></div>
        <div class="absolute -top-40 -left-40 -z-10 h-[32rem] w-[32rem] rounded-full bg-brand-600/30 blur-3xl"></div>
        <div class="absolute -right-32 bottom-0 -z-10 h-96 w-96 rounded-full bg-sky-500/20 blur-3xl"></div>

        <div class="mx-auto grid max-w-7xl items-center gap-14 px-4 py-20 sm:px-6 lg:grid-cols-2 lg:px-8 lg:py-28">
            <div>
                <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-brand-200 ring-1 ring-white/15">
                    <x-icon name="sparkles" class="h-3.5 w-3.5" /> Learning &middot; Testing &middot; Library
                </span>
                <h1 class="mt-6 font-display text-4xl leading-[1.1] font-bold tracking-tight text-white sm:text-5xl lg:text-6xl">
                    {{ $page->title ?? 'One platform for learning, testing, and your digital library' }}
                </h1>
                <p class="mt-6 max-w-xl text-lg leading-relaxed text-slate-300">
                    {{ $page->subtitle ?? 'e-Library brings Learning Management, Computer-Based Testing, and a Digital Library together — use them all, or pick just what your organization needs.' }}
                </p>
                <div class="mt-10 flex flex-col gap-3 sm:flex-row">
                    <x-button :href="route('signup')" variant="white" size="lg" icon-right="arrow-right">Create your workspace</x-button>
                    <x-button :href="route('login')" variant="outline-white" size="lg">Log in</x-button>
                </div>
                <p class="mt-6 flex items-center gap-2 text-sm text-slate-400">
                    <x-icon name="check-circle" class="h-4 w-4 text-emerald-400" /> Choose one module or all three &middot; No setup fees
                </p>
            </div>

            {{-- Product collage --}}
            <div class="relative mx-auto w-full max-w-lg" aria-hidden="true">
                <div class="rounded-3xl bg-white/5 p-3 ring-1 ring-white/10 backdrop-blur">
                    <div class="overflow-hidden rounded-2xl bg-white shadow-2xl">
                        <div class="relative h-40 bg-gradient-to-br from-brand-500 to-indigo-700">
                            <div class="bg-dots absolute inset-0"></div>
                            <x-icon name="academic-cap" class="absolute -right-4 -bottom-6 h-40 w-40 rotate-12 text-white/15" />
                            <span class="absolute top-4 left-4 rounded-full bg-white/20 px-2.5 py-0.5 text-[11px] font-semibold text-white">Course</span>
                        </div>
                        <div class="p-5">
                            <p class="font-semibold text-slate-900">Foundations of Data Analysis</p>
                            <p class="mt-1 text-xs text-slate-500">12 lessons &middot; Certificate</p>
                            <div class="mt-4 flex items-center gap-3">
                                <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-200"><div class="h-full w-2/3 rounded-full bg-brand-600"></div></div>
                                <span class="text-xs font-semibold text-slate-600">67%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="absolute -bottom-10 -left-6 w-56 rounded-2xl bg-white p-4 shadow-2xl ring-1 ring-slate-900/5 sm:-left-12">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 text-emerald-600"><x-icon name="check-circle" /></span>
                        <div>
                            <p class="text-xs text-slate-500">Exam result</p>
                            <p class="text-lg font-bold text-slate-900">86% <span class="text-xs font-semibold text-emerald-600">Passed</span></p>
                        </div>
                    </div>
                </div>

                <div class="absolute -top-8 -right-4 w-48 rounded-2xl bg-white p-4 shadow-2xl ring-1 ring-slate-900/5 sm:-right-10">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-100 text-amber-600"><x-icon name="book-open" /></span>
                        <div>
                            <p class="text-xs text-slate-500">Library</p>
                            <p class="text-sm font-semibold text-slate-900">Reading now</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Audiences --}}
    <section id="solutions" class="scroll-mt-20 border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <p class="text-center text-sm font-medium text-slate-500">Built for organizations that teach, test and share knowledge</p>
            <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                @foreach ($audiences as $audience)
                    <div class="flex items-center justify-center gap-2 rounded-xl py-3 text-sm font-semibold text-slate-600">
                        <x-icon :name="$audience['icon']" class="h-5 w-5 text-slate-400" /> {{ $audience['label'] }}
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CMS content from the published home page --}}
    @if ($page?->content)
        <section class="mx-auto max-w-3xl px-4 py-16 sm:px-6 lg:px-8">
            <div class="rich-text text-lg text-slate-700">{!! $page->content !!}</div>
        </section>
    @endif

    {{-- Modules --}}
    <section id="features" class="scroll-mt-20 bg-slate-50">
        <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8 lg:py-28">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-sm font-semibold uppercase tracking-wider text-brand-600">Three products, one platform</p>
                <h2 class="mt-3 font-display text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Choose the packages you need</h2>
                <p class="mt-4 text-lg text-slate-600">Every workspace picks its own combination at signup — use one, two, or all three.</p>
            </div>

            <div class="mt-16 space-y-20 lg:space-y-28">
                @foreach ($modules as $module)
                    @php($accent = $accents[$module['accent']])
                    <div id="{{ $module['id'] }}" class="grid scroll-mt-24 items-center gap-10 lg:grid-cols-2 lg:gap-16">
                        <div class="{{ $loop->even ? 'lg:order-2' : '' }}">
                            <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold {{ $accent['soft'] }}">
                                <x-icon :name="$module['icon']" class="h-4 w-4" /> {{ $module['eyebrow'] }}
                            </span>
                            <h3 class="mt-4 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">{{ $module['title'] }}</h3>
                            <p class="mt-4 text-base leading-relaxed text-slate-600">{{ $module['body'] }}</p>
                            <ul class="mt-6 space-y-3">
                                @foreach ($module['points'] as $point)
                                    <li class="flex gap-3 text-sm text-slate-700">
                                        <x-icon name="check-circle" class="h-5 w-5 {{ $accent['text'] }}" /> {{ $point }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        <div class="{{ $loop->even ? 'lg:order-1' : '' }}" aria-hidden="true">
                            <div class="relative isolate overflow-hidden rounded-3xl bg-gradient-to-br {{ $accent['from'] }} {{ $accent['to'] }} p-8 shadow-xl sm:p-10">
                                <div class="bg-dots absolute inset-0 -z-10"></div>
                                <x-icon :name="$module['icon']" class="absolute -right-10 -bottom-10 -z-10 h-64 w-64 rotate-12 text-white/10" />
                                <div class="space-y-3">
                                    @foreach ($module['points'] as $point)
                                        <div class="flex items-center gap-3 rounded-xl bg-white/95 p-4 shadow-sm">
                                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $accent['soft'] }}">
                                                <x-icon :name="['check', 'bolt', 'sparkles'][$loop->index]" class="h-4 w-4" />
                                            </span>
                                            <span class="text-sm font-medium text-slate-800">{{ \Illuminate\Support\Str::before($point, ',') }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Feature grid --}}
    <section class="bg-white">
        <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8 lg:py-24">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="font-display text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Everything you'd expect, built in</h2>
                <p class="mt-4 text-lg text-slate-600">The tools serious education providers rely on, without stitching together five different products.</p>
            </div>
            <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($features as $feature)
                    <div class="rounded-2xl border border-slate-200 p-6 transition hover:border-brand-200 hover:shadow-lg hover:shadow-brand-900/5">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                            <x-icon :name="$feature['icon']" />
                        </span>
                        <h3 class="mt-5 text-base font-semibold text-slate-900">{{ $feature['title'] }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $feature['body'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- How it works --}}
    <section id="how-it-works" class="scroll-mt-20 bg-slate-50">
        <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8 lg:py-24">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-sm font-semibold uppercase tracking-wider text-brand-600">How it works</p>
                <h2 class="mt-3 font-display text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Up and running in three steps</h2>
            </div>
            <ol class="mt-14 grid gap-8 md:grid-cols-3">
                @foreach ([
                    ['title' => 'Create your workspace', 'body' => 'Tell us about your organization and pick Learning, Testing, Library — or all three.'],
                    ['title' => 'Get approved', 'body' => 'Our team reviews new workspaces quickly. You\'ll get an email as soon as you\'re live.'],
                    ['title' => 'Add content & invite people', 'body' => 'Publish courses, exams and books, then add learners and staff to your workspace.'],
                ] as $step)
                    <li class="relative rounded-2xl bg-white p-8 ring-1 ring-slate-200">
                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-ink-900 font-display text-lg font-bold text-white">{{ $loop->iteration }}</span>
                        <h3 class="mt-5 text-lg font-semibold text-slate-900">{{ $step['title'] }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $step['body'] }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- Find your workspace (for learners) --}}
    <section id="find-workspace" class="scroll-mt-20 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
            <div class="grid items-center gap-10 rounded-3xl bg-gradient-to-br from-brand-50 to-sky-50 p-8 ring-1 ring-brand-100 sm:p-12 lg:grid-cols-2">
                <div>
                    <h2 class="font-display text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Are you a learner?</h2>
                    <p class="mt-3 text-base text-slate-600">Your school or organization has its own workspace. Enter its address to go straight to your login page.</p>
                </div>
                <form method="GET" action="{{ route('workspace.find') }}">
                    <label for="workspace" class="text-sm font-medium text-slate-700">Workspace address</label>
                    <div class="mt-2 flex flex-col gap-3 sm:flex-row">
                        <div class="flex flex-1 items-center rounded-xl bg-white ring-1 ring-slate-300 focus-within:ring-2 focus-within:ring-brand-600">
                            <span class="pl-4 text-sm text-slate-400 select-none">{{ parse_url(url('/'), PHP_URL_HOST) }}/t/</span>
                            <input id="workspace" name="workspace" value="{{ old('workspace') }}" placeholder="your-school" required
                                   class="block w-full border-0 bg-transparent py-3 pr-4 pl-0.5 text-sm text-slate-900 placeholder:text-slate-400 focus:ring-0 focus:outline-none">
                        </div>
                        <x-button type="submit" variant="dark" icon-right="arrow-right">Continue</x-button>
                    </div>
                    @if (session('workspace_error'))
                        <p class="mt-2 text-sm text-red-600">{{ session('workspace_error') }}</p>
                    @endif
                </form>
            </div>
        </div>
    </section>

    {{-- Closing CTA --}}
    <section class="relative isolate overflow-hidden bg-ink-950">
        <div class="bg-dots absolute inset-0 -z-10"></div>
        <div class="absolute top-0 left-1/2 -z-10 h-80 w-[40rem] -translate-x-1/2 rounded-full bg-brand-600/25 blur-3xl"></div>
        <div class="mx-auto max-w-4xl px-4 py-20 text-center sm:px-6 lg:px-8 lg:py-24">
            <h2 class="font-display text-3xl font-bold tracking-tight text-white sm:text-4xl">Ready to get started?</h2>
            <p class="mx-auto mt-4 max-w-xl text-lg text-slate-300">
                Sign up in a couple of minutes — your workspace goes live once our team approves it.
                @if ($workspaceCount > 0)
                    Join {{ number_format($workspaceCount) }} {{ \Illuminate\Support\Str::plural('organization', $workspaceCount) }} already using e-Library.
                @endif
            </p>
            <div class="mt-10 flex flex-col justify-center gap-3 sm:flex-row">
                <x-button :href="route('signup')" variant="white" size="lg">Create your workspace</x-button>
                <x-button :href="route('login')" variant="outline-white" size="lg">Log in</x-button>
            </div>
        </div>
    </section>
</x-marketing-layout>
