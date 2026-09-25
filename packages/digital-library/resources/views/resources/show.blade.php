<x-app-layout :title="$resource->title">
    <a href="{{ route('library.resources.index') }}" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-700">
        &larr; Back to library
    </a>

    <x-card>
        @if ($resource->cover_image_path)
            <img src="{{ Illuminate\Support\Facades\Storage::disk('public')->url($resource->cover_image_path) }}" alt="" class="h-40 w-28 rounded-lg object-cover ring-1 ring-slate-200">
        @else
            <div class="flex h-14 w-14 items-center justify-center rounded-xl {{ \App\Enums\Module::Library->softClasses() }}">
                <x-module-icon module="library" class="h-7 w-7" />
            </div>
        @endif

        <div class="mt-4 flex items-start justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold text-slate-900">{{ $resource->title }}</h1>
                @if ($resource->author)
                    <p class="mt-1 text-sm text-slate-500">by {{ $resource->author }}</p>
                @endif
            </div>
            <form method="POST" action="{{ $isFavorited ? route('library.favorites.destroy', $resource) : route('library.favorites.store', $resource) }}">
                @csrf
                @if ($isFavorited)
                    @method('DELETE')
                @endif
                <button type="submit" class="rounded-lg px-3 py-1.5 text-sm font-medium {{ $isFavorited ? 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-200' : 'bg-white text-slate-600 ring-1 ring-inset ring-slate-300 hover:bg-slate-50' }}">
                    {{ $isFavorited ? '★ Favorited' : '☆ Favorite' }}
                </button>
            </form>
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-1.5">
            <x-badge color="amber">{{ $resource->category }}</x-badge>
            @foreach ($resource->tags as $tag)
                <span class="rounded-full bg-slate-50 px-2 py-0.5 text-xs text-slate-500 ring-1 ring-inset ring-slate-200">#{{ $tag->name }}</span>
            @endforeach
            @if ($resource->ratingsCount() > 0)
                <span class="text-xs text-slate-500">★ {{ $resource->averageRating() }} ({{ $resource->ratingsCount() }})</span>
            @endif
        </div>

        @if ($resource->isbn || $resource->publisher || $resource->publication_year || $resource->language)
            <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-1 text-sm sm:grid-cols-4">
                @if ($resource->publisher)
                    <div><dt class="text-xs text-slate-400">Publisher</dt><dd class="text-slate-700">{{ $resource->publisher }}</dd></div>
                @endif
                @if ($resource->publication_year)
                    <div><dt class="text-xs text-slate-400">Year</dt><dd class="text-slate-700">{{ $resource->publication_year }}</dd></div>
                @endif
                @if ($resource->language)
                    <div><dt class="text-xs text-slate-400">Language</dt><dd class="text-slate-700">{{ $resource->language }}</dd></div>
                @endif
                @if ($resource->isbn)
                    <div><dt class="text-xs text-slate-400">ISBN</dt><dd class="text-slate-700">{{ $resource->isbn }}</dd></div>
                @endif
            </dl>
        @endif

        @if ($resource->description)
            <div class="rich-text mt-6 text-sm text-slate-700">{!! $resource->description !!}</div>
        @endif

        @if ($resource->external_url)
            <div class="mt-8">
                <x-button :href="$resource->external_url" target="_blank" rel="noopener noreferrer">
                    Open resource
                </x-button>
            </div>
        @endif

        @if ($resource->requires_checkout)
            <div class="mt-8 border-t border-slate-100 pt-6">
                @if ($myCheckout)
                    <p class="text-sm text-slate-700">
                        You have this checked out &mdash; due {{ $myCheckout->due_at->format('M j, Y') }}
                        @if ($myCheckout->isOverdue())
                            <span class="text-red-600">(overdue)</span>
                        @endif
                    </p>
                    <form method="POST" action="{{ route('library.checkouts.return', $myCheckout) }}" class="mt-3">
                        @csrf
                        <x-button type="submit" variant="secondary">Return</x-button>
                    </form>
                @elseif ($myHold && $myHold->hasLiveOffer())
                    <p class="text-sm text-emerald-700">A copy is ready for you &mdash; claim by {{ $myHold->expires_at->format('M j, Y') }}.</p>
                    <form method="POST" action="{{ route('library.holds.claim', $myHold) }}" class="mt-3">
                        @csrf
                        <x-button type="submit">Claim your copy</x-button>
                    </form>
                @elseif ($myHold)
                    <p class="text-sm text-slate-600">You're #{{ $myHold->position() }} on the waitlist.</p>
                @elseif ($resource->pricing_policy->value === 'paid' && ! $resource->isPurchasedBy(auth()->user()))
                    <x-button :href="route('library.resources.purchase.create', $resource)">
                        Buy for {{ number_format($resource->price, 2) }} {{ $resource->currency }}
                    </x-button>
                @else
                    <form method="POST" action="{{ route('library.resources.borrow', $resource) }}">
                        @csrf
                        <x-button type="submit">
                            {{ $resource->hasCopyAvailable() ? 'Borrow' : 'Join waitlist' }}
                        </x-button>
                    </form>
                    @if (! $resource->hasCopyAvailable())
                        <p class="mt-2 text-xs text-slate-500">No copies available right now.</p>
                    @endif
                @endif
            </div>
        @endif

        @if ($resource->files->isNotEmpty())
            @php($hasAccess = $resource->isAccessibleTo(auth()->user()))
            <div class="mt-8 space-y-3 border-t border-slate-100 pt-6">
                <h2 class="text-sm font-semibold text-slate-900">Files</h2>
                @if (! $hasAccess)
                    <p class="text-sm text-amber-600">This resource requires checkout to access its files.</p>
                @else
                    @foreach ($resource->files as $file)
                        <div class="rounded-lg border border-slate-200 p-4">
                            <p class="mb-2 text-sm font-medium text-slate-900">{{ $file->title }}</p>
                            @if (in_array($file->format->value, ['pdf', 'epub']))
                                <a href="{{ route('library.resources.files.read', [$resource, $file]) }}" class="text-sm font-medium text-brand-600 hover:text-brand-500">Read &rarr;</a>
                                @if ($file->access_level->value === 'open')
                                    &middot; <a href="{{ route('library.resources.files.download', [$resource, $file]) }}" class="text-sm font-medium text-brand-600 hover:text-brand-500">Download</a>
                                @endif
                            @elseif ($file->access_level->value === 'open')
                                <a href="{{ route('library.resources.files.download', [$resource, $file]) }}" class="text-sm font-medium text-brand-600 hover:text-brand-500">Download &rarr;</a>
                            @elseif ($file->format->value === 'audio')
                                <audio controls preload="none" class="w-full max-w-xl" src="{{ route('library.resources.files.stream', [$resource, $file]) }}"></audio>
                            @else
                                <a href="{{ route('library.resources.files.stream', [$resource, $file]) }}" target="_blank" class="text-sm font-medium text-brand-600 hover:text-brand-500">View &rarr;</a>
                            @endif
                        </div>
                    @endforeach
                @endif
            </div>
        @endif

        <div class="mt-8 border-t border-slate-100 pt-6">
            <h2 class="mb-3 text-sm font-semibold text-slate-900">{{ $myRating ? 'Update your rating' : 'Rate this resource' }}</h2>
            <form method="POST" action="{{ route('library.resources.ratings.store', $resource) }}" class="space-y-3">
                @csrf
                <div class="flex gap-1">
                    @for ($i = 1; $i <= 5; $i++)
                        <label class="cursor-pointer text-2xl text-amber-400">
                            <input type="radio" name="stars" value="{{ $i }}" class="sr-only peer" @checked(old('stars', $myRating?->stars) == $i) required>
                            <span class="peer-checked:inline hidden">★</span>
                            <span class="peer-checked:hidden">☆</span>
                        </label>
                    @endfor
                </div>
                <textarea name="comment" rows="3" placeholder="Optional comment" class="block w-full rounded-lg border-0 px-3 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:text-sm">{{ old('comment', $myRating?->comment) }}</textarea>
                <x-button type="submit" variant="secondary">{{ $myRating ? 'Update rating' : 'Submit rating' }}</x-button>
            </form>
        </div>
    </x-card>
</x-app-layout>
