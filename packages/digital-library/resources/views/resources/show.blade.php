@php
    $ratingsCount = $resource->ratingsCount();
    $averageRating = $ratingsCount > 0 ? $resource->averageRating() : null;
@endphp

<x-app-layout :title="$resource->title">
    <nav class="mb-8 flex items-center gap-2 text-sm text-slate-500">
        <a href="{{ route('library.resources.index') }}" class="hover:text-slate-800">Library</a>
        <x-icon name="chevron-right" class="h-4 w-4 text-slate-300" />
        @if ($resource->category)
            <a href="{{ route('library.resources.index', ['category' => $resource->category]) }}" class="hover:text-slate-800">{{ $resource->category }}</a>
            <x-icon name="chevron-right" class="h-4 w-4 text-slate-300" />
        @endif
        <span class="truncate text-slate-800">{{ $resource->title }}</span>
    </nav>

    <div class="grid gap-10 lg:grid-cols-[18rem_1fr]">
        {{-- Cover and actions --}}
        <aside class="space-y-5">
            <div class="mx-auto aspect-[3/4] w-56 overflow-hidden rounded-2xl shadow-2xl shadow-slate-900/20 ring-1 ring-slate-900/5 lg:w-full">
                @if ($resource->cover_image_path)
                    <img src="{{ Illuminate\Support\Facades\Storage::disk('public')->url($resource->cover_image_path) }}" alt="" class="h-full w-full object-cover">
                @else
                    <x-cover :seed="$resource->title" icon="book-open" class="h-full w-full">
                        <p class="absolute inset-x-5 bottom-5 font-display text-2xl leading-tight font-bold text-white">{{ $resource->title }}</p>
                    </x-cover>
                @endif
            </div>

            @if ($resource->external_url || $resource->requires_checkout)
                <x-card class="space-y-4">
                    @if ($resource->external_url)
                        <x-button :href="$resource->external_url" target="_blank" rel="noopener noreferrer" class="w-full" icon-right="external">
                            Open resource
                        </x-button>
                    @endif

                    @if ($resource->requires_checkout)
                        @if ($myCheckout)
                            <div class="rounded-lg bg-slate-50 p-3 text-sm text-slate-700">
                                You have this checked out &mdash; due {{ $myCheckout->due_at->format('M j, Y') }}
                                @if ($myCheckout->isOverdue())
                                    <span class="font-semibold text-red-600">(overdue)</span>
                                @endif
                            </div>
                            <form method="POST" action="{{ route('library.checkouts.return', $myCheckout) }}">
                                @csrf
                                <x-button type="submit" variant="secondary" class="w-full">Return</x-button>
                            </form>
                        @elseif ($myHold && $myHold->hasLiveOffer())
                            <p class="rounded-lg bg-emerald-50 p-3 text-sm text-emerald-800">A copy is ready for you &mdash; claim by {{ $myHold->expires_at->format('M j, Y') }}.</p>
                            <form method="POST" action="{{ route('library.holds.claim', $myHold) }}">
                                @csrf
                                <x-button type="submit" class="w-full">Claim your copy</x-button>
                            </form>
                        @elseif ($myHold)
                            <p class="rounded-lg bg-slate-50 p-3 text-center text-sm text-slate-600">You're #{{ $myHold->position() }} on the waitlist.</p>
                        @elseif ($resource->pricing_policy->value === 'paid' && ! $resource->isPurchasedBy(auth()->user()))
                            <p class="text-2xl font-bold text-slate-900">{{ $resource->currency }} {{ number_format($resource->price, 2) }}</p>
                            <x-button :href="route('library.resources.purchase.create', $resource)" class="w-full">
                                Buy for {{ number_format($resource->price, 2) }} {{ $resource->currency }}
                            </x-button>
                        @else
                            <form method="POST" action="{{ route('library.resources.borrow', $resource) }}">
                                @csrf
                                <x-button type="submit" class="w-full" icon="bookmark">
                                    {{ $resource->hasCopyAvailable() ? 'Borrow' : 'Join waitlist' }}
                                </x-button>
                            </form>
                            @if (! $resource->hasCopyAvailable())
                                <p class="text-center text-xs text-slate-500">No copies available right now.</p>
                            @endif
                        @endif
                    @endif
                </x-card>
            @endif
        </aside>

        {{-- Details --}}
        <div class="min-w-0 space-y-8">
            <div>
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h1 class="font-display text-3xl leading-tight font-bold tracking-tight text-slate-900 sm:text-4xl">{{ $resource->title }}</h1>
                        @if ($resource->author)
                            <p class="mt-2 text-base text-slate-600">by <span class="font-medium text-slate-800">{{ $resource->author }}</span></p>
                        @endif
                    </div>
                    <form method="POST" action="{{ $isFavorited ? route('library.favorites.destroy', $resource) : route('library.favorites.store', $resource) }}">
                        @csrf
                        @if ($isFavorited)
                            @method('DELETE')
                        @endif
                        <button type="submit" class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium {{ $isFavorited ? 'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-200' : 'bg-white text-slate-600 ring-1 ring-inset ring-slate-300 hover:bg-slate-50' }}">
                            <x-icon name="heart" class="h-4 w-4 {{ $isFavorited ? 'fill-rose-500 text-rose-500' : '' }}" />
                            {{ $isFavorited ? 'Favorited' : 'Favorite' }}
                        </button>
                    </form>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-2">
                    @if ($resource->category)
                        <x-badge color="amber">{{ $resource->category }}</x-badge>
                    @endif
                    @foreach ($resource->tags as $tag)
                        <a href="{{ route('library.resources.index', ['tag' => $tag->slug]) }}" class="rounded-full bg-white px-2.5 py-0.5 text-xs text-slate-500 ring-1 ring-inset ring-slate-200 hover:bg-slate-50">#{{ $tag->name }}</a>
                    @endforeach
                    @if ($averageRating !== null)
                        <span class="inline-flex items-center gap-1 text-sm text-slate-600">
                            <span class="text-amber-400">★</span>
                            <span class="font-semibold text-slate-900">{{ $averageRating }}</span>
                            <span class="text-slate-400">({{ $ratingsCount }} {{ \Illuminate\Support\Str::plural('rating', $ratingsCount) }})</span>
                        </span>
                    @endif
                </div>
            </div>

            @if ($resource->isbn || $resource->publisher || $resource->publication_year || $resource->language)
                <dl class="grid grid-cols-2 gap-4 rounded-2xl bg-white p-5 ring-1 ring-slate-200/80 sm:grid-cols-4">
                    @if ($resource->publisher)
                        <div><dt class="text-xs text-slate-400">Publisher</dt><dd class="mt-0.5 text-sm font-medium text-slate-800">{{ $resource->publisher }}</dd></div>
                    @endif
                    @if ($resource->publication_year)
                        <div><dt class="text-xs text-slate-400">Year</dt><dd class="mt-0.5 text-sm font-medium text-slate-800">{{ $resource->publication_year }}</dd></div>
                    @endif
                    @if ($resource->language)
                        <div><dt class="text-xs text-slate-400">Language</dt><dd class="mt-0.5 text-sm font-medium text-slate-800">{{ $resource->language }}</dd></div>
                    @endif
                    @if ($resource->isbn)
                        <div><dt class="text-xs text-slate-400">ISBN</dt><dd class="mt-0.5 text-sm font-medium text-slate-800">{{ $resource->isbn }}</dd></div>
                    @endif
                </dl>
            @endif

            @if ($resource->description)
                <section>
                    <h2 class="text-lg font-bold text-slate-900">Description</h2>
                    <div class="rich-text mt-3 text-[15px] text-slate-700">{!! $resource->description !!}</div>
                </section>
            @endif

            @if ($resource->files->isNotEmpty())
                @php($hasAccess = $resource->isAccessibleTo(auth()->user()))
                <section>
                    <h2 class="text-lg font-bold text-slate-900">Files</h2>
                    @if (! $hasAccess)
                        <p class="mt-3 flex items-center gap-2 rounded-xl bg-amber-50 p-4 text-sm text-amber-800 ring-1 ring-amber-200">
                            <x-icon name="lock" class="h-4 w-4" /> This resource requires checkout to access its files.
                        </p>
                    @else
                        <div class="mt-3 divide-y divide-slate-100 overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200/80">
                            @foreach ($resource->files as $file)
                                <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-[11px] font-bold uppercase text-slate-600">{{ $file->format->value }}</span>
                                        <p class="text-sm font-medium text-slate-900">{{ $file->title }}</p>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        @if (in_array($file->format->value, ['pdf', 'epub']))
                                            <x-button :href="route('library.resources.files.read', [$resource, $file])" size="sm" icon="book-open">Read</x-button>
                                            @if ($file->access_level->value === 'open')
                                                <x-button :href="route('library.resources.files.download', [$resource, $file])" variant="secondary" size="sm" icon="download">Download</x-button>
                                            @endif
                                        @elseif ($file->access_level->value === 'open')
                                            <x-button :href="route('library.resources.files.download', [$resource, $file])" variant="secondary" size="sm" icon="download">Download</x-button>
                                        @elseif ($file->format->value === 'audio')
                                            <audio controls preload="none" class="w-full max-w-sm" src="{{ route('library.resources.files.stream', [$resource, $file]) }}"></audio>
                                        @else
                                            <x-button :href="route('library.resources.files.stream', [$resource, $file])" target="_blank" variant="secondary" size="sm" icon="eye">View</x-button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>
            @endif

            <section class="rounded-2xl bg-white p-6 ring-1 ring-slate-200/80">
                <h2 class="mb-3 text-base font-semibold text-slate-900">{{ $myRating ? 'Update your rating' : 'Rate this resource' }}</h2>
                <form method="POST" action="{{ route('library.resources.ratings.store', $resource) }}" class="space-y-3">
                    @csrf
                    <div class="flex gap-1">
                        @for ($i = 1; $i <= 5; $i++)
                            <label class="cursor-pointer text-3xl text-amber-400">
                                <input type="radio" name="stars" value="{{ $i }}" class="sr-only peer" @checked(old('stars', $myRating?->stars) == $i) required>
                                <span class="peer-checked:inline hidden">★</span>
                                <span class="peer-checked:hidden">☆</span>
                            </label>
                        @endfor
                    </div>
                    <textarea name="comment" rows="3" placeholder="Optional comment" class="block w-full rounded-lg border-0 px-3 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:text-sm">{{ old('comment', $myRating?->comment) }}</textarea>
                    <x-button type="submit" variant="secondary">{{ $myRating ? 'Update rating' : 'Submit rating' }}</x-button>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>
