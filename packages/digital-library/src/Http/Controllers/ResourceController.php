<?php

namespace Elibrary\Library\Http\Controllers;

use App\Http\Controllers\Controller;
use Elibrary\Library\Enums\LibraryPaymentGateway;
use Elibrary\Library\Models\LibraryResource;
use Elibrary\Library\Payments\LibraryPaymentConfirmation;
use Elibrary\Library\Models\ResourceTag;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class ResourceController extends Controller
{
    public function index(Request $request): View
    {
        $query = LibraryResource::query()->where('is_published', true);

        if ($search = $request->string('q')->trim()->limit(100, '')->value()) {
            // % and _ are matched literally, not as wildcards.
            // The explicit ESCAPE makes this work the same on MariaDB and SQLite.
            $like = '%'.addcslashes($search, '%_\\').'%';
            $query->where(function ($inner) use ($like) {
                foreach (['title', 'author', 'publisher', 'isbn'] as $column) {
                    $inner->orWhereRaw("{$column} like ? escape ?", [$like, '\\']);
                }
            });
        }

        if ($category = $request->string('category')->trim()->value()) {
            $query->where('category', $category);
        }

        if ($tag = $request->string('tag')->trim()->value()) {
            $query->whereHas('tags', fn ($inner) => $inner->where('slug', $tag));
        }

        $sort = $request->string('sort')->trim()->value() ?: 'title';
        match ($sort) {
            'newest' => $query->latest(),
            'publication_year' => $query->orderByDesc('publication_year'),
            default => $query->orderBy('title'),
        };

        return view('library::resources.index', [
            'resources' => $query->with('tags')->paginate(24)->withQueryString(),
            'categories' => LibraryResource::query()->where('is_published', true)->distinct()->orderBy('category')->pluck('category'),
            'tags' => ResourceTag::query()->whereHas('resources', fn ($q) => $q->where('is_published', true))->orderBy('name')->get(),
            'search' => $search ?? '',
            'activeCategory' => $category ?? '',
            'activeTag' => $tag ?? '',
            'activeSort' => $sort,
        ]);
    }

    public function show(Request $request, string $tenant, LibraryResource $resource): View
    {
        abort_unless($resource->is_published, 404);

        $user = $request->user();
        $this->confirmReturningPayment($request, $resource);

        return view('library::resources.show', [
            'resource' => $resource->load('tags', 'files'),
            'myCheckout' => $resource->checkouts()->where('user_id', $user->id)->whereNull('returned_at')->first(),
            // A lapsed offer is over (the hourly job clears it); they can queue again.
            'myHold' => $resource->holds()->where('user_id', $user->id)->whereNull('fulfilled_at')->get()->reject->hasForfeitedOffer()->first(),
            'myRating' => $resource->ratingFor($user),
            'isFavorited' => $resource->isFavoritedBy($user),
            'needsPurchase' => $resource->needsPurchaseBy($user),
            'pendingPurchase' => $resource->pendingPurchaseFor($user),
            'hasAccess' => $resource->isAccessibleTo($user),
        ]);
    }

    /**
     * Card gateways send the learner back here after checkout (Paystack adds
     * ?reference=, Flutterwave ?tx_ref=, Stripe ?paid=1). Confirm their
     * pending payment with the gateway right away rather than waiting on the
     * webhook, which may be late or not set up.
     */
    private function confirmReturningPayment(Request $request, LibraryResource $resource): void
    {
        if (! $request->hasAny(['reference', 'trxref', 'tx_ref', 'transaction_id', 'paid'])) {
            return;
        }

        // Someone who started checkout twice may have paid the older attempt.
        $pending = $resource->purchases()
            ->where('user_id', $request->user()->id)
            ->where('status', 'pending')
            ->where('gateway', '!=', LibraryPaymentGateway::BankTransfer->value)
            ->latest('id')->take(3)->get();

        if ($pending->isEmpty()) {
            return;
        }

        $confirmation = app(LibraryPaymentConfirmation::class);
        foreach ($pending as $purchase) {
            try {
                if ($confirmation->confirm($purchase)) {
                    session()->now('status', __('Payment received — this resource is yours.'));

                    return;
                }
            } catch (Throwable $e) {
                report($e);
            }
        }

        session()->now('status', __("We're still waiting for the payment provider to confirm your payment. Refresh in a minute."));
    }
}
