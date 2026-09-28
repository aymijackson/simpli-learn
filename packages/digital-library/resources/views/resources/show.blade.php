@php
    $ratingsCount = $resource->ratingsCount();
    $averageRating = $ratingsCount > 0 ? $resource->averageRating() : null;
@endphp

<x-app-layout :title="$resource->title">
    <nav class="mb-8 flex items-center gap-2 text-sm text-slate-500">
        <a href="{{ route('library.resources.index') }}" class="hover:text-slate-800">{{ __('Library') }}</a>
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

            @if ($resource->external_url || $resource->requires_checkout || $resource->isPaid())
                <x-card class="space-y-4">
                    @if ($needsPurchase)
                        {{-- A paid resource is bought first, whether or not it is also lent out. --}}
                        <p class="text-2xl font-bold text-slate-900">{{ $resource->currency }} {{ number_format($resource->price, 2) }}</p>
                        @if ($pendingPurchase?->gateway?->value === 'bank_transfer' && ! empty($pendingPurchase->metadata['reported_paid_at']))
                            <p class="rounded-lg bg-amber-50 p-3 text-sm text-amber-800">{{ __('Your bank transfer is awaiting confirmation by the organization.') }}</p>
                        @else
                            <x-button :href="route('library.resources.purchase.create', $resource)" class="w-full">
                                {{ __('Buy for :price :currency', ['price' => number_format($resource->price, 2), 'currency' => $resource->currency]) }}
                            </x-button>
                        @endif
                    @else
                        @if ($resource->external_url && $hasAccess)
                            <x-button :href="$resource->external_url" target="_blank" rel="noopener noreferrer" class="w-full" icon-right="external">
                                {{ __('Open resource') }}
                            </x-button>
                        @endif

                        @if ($resource->requires_checkout)
                            @if ($myCheckout)
                                @if ($myCheckout->isOverdue())
                                    <p class="rounded-lg bg-amber-50 p-3 text-sm text-amber-800">{{ __('Your loan ended on :due_at. It will be returned automatically.', ['due_at' => $myCheckout->due_at->translatedFormat('M j, Y')]) }}</p>
                                @else
                                    <p class="rounded-lg bg-slate-50 p-3 text-sm text-slate-700">{{ __('You have this checked out — due :due_at.', ['due_at' => $myCheckout->due_at->translatedFormat('M j, Y')]) }}</p>
                                @endif
                                <form method="POST" action="{{ route('library.checkouts.return', $myCheckout) }}">
                                    @csrf
                                    <x-button type="submit" variant="secondary" class="w-full">{{ __('Return') }}</x-button>
                                </form>
                            @elseif ($myHold && $myHold->hasLiveOffer())
                                <p class="rounded-lg bg-emerald-50 p-3 text-sm text-emerald-800">{{ __('A copy is ready for you — claim by :expires_at.', ['expires_at' => $myHold->expires_at->translatedFormat('M j, Y')]) }}</p>
                                <form method="POST" action="{{ route('library.holds.claim', $myHold) }}">
                                    @csrf
                                    <x-button type="submit" class="w-full">{{ __('Claim your copy') }}</x-button>
                                </form>
                                <form method="POST" action="{{ route('library.holds.destroy', $myHold) }}">
                                    @csrf
                                    @method('DELETE')
                                    <x-button type="submit" variant="ghost" size="sm" class="w-full">{{ __('No thanks, pass it on') }}</x-button>
                                </form>
                            @elseif ($myHold)
                                <p class="rounded-lg bg-slate-50 p-3 text-center text-sm text-slate-600">{{ __('You\'re #:position on the waitlist. We\'ll let you know when a copy is ready.', ['position' => $myHold->position()]) }}</p>
                                <form method="POST" action="{{ route('library.holds.destroy', $myHold) }}">
                                    @csrf
                                    @method('DELETE')
                                    <x-button type="submit" variant="ghost" size="sm" class="w-full">{{ __('Leave the waitlist') }}</x-button>
                                </form>
                            @elseif (! $hasAccess)
                                <form method="POST" action="{{ route('library.resources.borrow', $resource) }}">
                                    @csrf
                                    <x-button type="submit" class="w-full" icon="bookmark">
                                        {{ $resource->hasCopyAvailable() ? __('Borrow') : __('Join waitlist') }}
                                    </x-button>
                                </form>
                                @if (! $resource->hasCopyAvailable())
                                    <p class="text-center text-xs text-slate-500">{{ __('No copies available right now.') }}</p>
                                @endif
                            @endif
                        @endif

                        @if ($resource->isPaid() && ! $resource->requires_checkout && $resource->isPurchasedBy(auth()->user()))
                            <p class="flex items-center gap-2 text-sm text-emerald-700"><x-icon name="check" class="h-4 w-4" /> {{ __('You own this resource.') }}</p>
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
                            <p class="mt-2 text-base text-slate-600">{!! __('by :author', ['author' => '<span class="font-medium text-slate-800">'.e($resource->author).'</span>']) !!}</p>
                        @endif
                    </div>
                    <form method="POST" action="{{ $isFavorited ? route('library.favorites.destroy', $resource) : route('library.favorites.store', $resource) }}">
                        @csrf
                        @if ($isFavorited)
                            @method('DELETE')
                        @endif
                        <button type="submit" class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium {{ $isFavorited ? 'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-200' : 'bg-white text-slate-600 ring-1 ring-inset ring-slate-300 hover:bg-slate-50' }}">
                            <x-icon name="heart" class="h-4 w-4 {{ $isFavorited ? 'fill-rose-500 text-rose-500' : '' }}" />
                            {{ $isFavorited ? __('Favorited') : __('Favorite') }}
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
                            <span class="text-slate-500">({{ $ratingsCount }} {{ trans_choice('rating|ratings', $ratingsCount) }})</span>
                        </span>
                    @endif
                </div>
            </div>

            @if ($resource->isbn || $resource->publisher || $resource->publication_year || $resource->language)
                <dl class="grid grid-cols-2 gap-4 rounded-2xl bg-white p-5 ring-1 ring-slate-200/80 sm:grid-cols-4">
                    @if ($resource->publisher)
                        <div><dt class="text-xs text-slate-500">{{ __('Publisher') }}</dt><dd class="mt-0.5 text-sm font-medium text-slate-800">{{ $resource->publisher }}</dd></div>
                    @endif
                    @if ($resource->publication_year)
                        <div><dt class="text-xs text-slate-500">{{ __('Year') }}</dt><dd class="mt-0.5 text-sm font-medium text-slate-800">{{ $resource->publication_year }}</dd></div>
                    @endif
                    @if ($resource->language)
                        <div><dt class="text-xs text-slate-500">{{ __('Language') }}</dt><dd class="mt-0.5 text-sm font-medium text-slate-800">{{ $resource->language }}</dd></div>
                    @endif
                    @if ($resource->isbn)
                        <div><dt class="text-xs text-slate-500">{{ __('ISBN') }}</dt><dd class="mt-0.5 text-sm font-medium text-slate-800">{{ $resource->isbn }}</dd></div>
                    @endif
                </dl>
            @endif

            @if ($resource->description)
                <section>
                    <h2 class="text-lg font-bold text-slate-900">{{ __('Description') }}</h2>
                    <div class="rich-text mt-3 text-[15px] text-slate-700">{!! $resource->description !!}</div>
                </section>
            @endif

            @if ($resource->files->isNotEmpty())
                <section>
                    <h2 class="text-lg font-bold text-slate-900">{{ __('Files') }}</h2>
                    @if (! $hasAccess)
                        <p class="mt-3 flex items-center gap-2 rounded-xl bg-amber-50 p-4 text-sm text-amber-800 ring-1 ring-amber-200">
                            <x-icon name="lock" class="h-4 w-4" /> {{ $needsPurchase ? __('Buy this resource to access its files.') : __('Borrow this resource to access its files.') }}
                        </p>
                    @else
                        <div class="mt-3 divide-y divide-slate-100 overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200/80">
                            @foreach ($resource->files as $file)
                                <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-10 w-12 items-center justify-center rounded-lg bg-slate-100 text-[10px] font-bold uppercase text-slate-600">{{ $file->format->badge() }}</span>
                                        <p class="text-sm font-medium text-slate-900">{{ $file->title }}</p>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        @php($viewer = $file->viewer())
                                        @if ($viewer)
                                            <x-button :href="route('library.resources.files.read', [$resource, $file])" size="sm" :icon="in_array($viewer, ['pdf', 'epub', 'text']) ? 'book-open' : 'eye'">
                                                {{ match ($viewer) { 'pdf', 'epub', 'text' => __('Read'), 'video' => __('Watch'), 'audio' => __('Listen'), default => __('View') } }}
                                            </x-button>
                                        @endif
                                        @if ($file->access_level->value === 'open')
                                            <x-button :href="route('library.resources.files.download', [$resource, $file])" variant="secondary" size="sm" icon="download">{{ __('Download') }}</x-button>
                                        @elseif (! $viewer)
                                            <span class="text-xs text-slate-500">{{ __('Not viewable online') }}</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>
            @endif

            @if ($resource->canBeRatedBy(auth()->user()))
                <section class="rounded-2xl bg-white p-6 ring-1 ring-slate-200/80">
                    <h2 id="rate-heading" class="mb-3 text-base font-semibold text-slate-900">{{ $myRating ? __('Update your rating') : __('Rate this resource') }}</h2>
                    <form method="POST" action="{{ route('library.resources.ratings.store', $resource) }}" class="space-y-3">
                        @csrf
                        <fieldset class="flex gap-1" aria-labelledby="rate-heading">
                            @for ($i = 1; $i <= 5; $i++)
                                <label class="cursor-pointer text-3xl text-amber-400">
                                    <input type="radio" name="stars" value="{{ $i }}" class="sr-only peer" @checked(old('stars', $myRating?->stars) == $i) required>
                                    <span class="sr-only">{{ trans_choice(':count star|:count stars', $i) }}</span>
                                    <span class="peer-checked:inline hidden" aria-hidden="true">★</span>
                                    <span class="peer-checked:hidden" aria-hidden="true">☆</span>
                                </label>
                            @endfor
                        </fieldset>
                        <textarea name="comment" rows="3" aria-label="{{ __('Optional comment') }}" placeholder="{{ __('Optional comment') }}" class="block w-full rounded-lg border-0 px-3 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600 sm:text-sm">{{ old('comment', $myRating?->comment) }}</textarea>
                        <x-button type="submit" variant="secondary">{{ $myRating ? __('Update rating') : __('Submit rating') }}</x-button>
                    </form>
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
