<?php

namespace Elibrary\Library\Checkouts;

use App\Models\User;
use Elibrary\Library\Models\LibraryCheckout;
use Elibrary\Library\Models\LibraryHold;
use Elibrary\Library\Models\LibraryResource;
use Illuminate\Support\Facades\DB;

class LibraryCheckoutService
{
    /**
     * The single borrow/waitlist decision point. If a copy is free, checks
     * the resource out directly; otherwise joins (or reuses) the waitlist.
     * Never creates a duplicate active checkout or a duplicate open hold for
     * the same user.
     */
    public function borrow(LibraryResource $resource, User $user): LibraryCheckout|LibraryHold
    {
        return DB::transaction(function () use ($resource, $user) {
            $resource = LibraryResource::query()->whereKey($resource->id)->lockForUpdate()->firstOrFail();

            abort_if(
                $resource->pricing_policy->value === 'paid' && ! $resource->isPurchasedBy($user),
                403,
                'Purchase this resource before borrowing it.'
            );

            $existingCheckout = $resource->checkouts()->where('user_id', $user->id)->whereNull('returned_at')->first();
            if ($existingCheckout) {
                return $existingCheckout;
            }

            $existingHold = $resource->holds()->where('user_id', $user->id)->whereNull('fulfilled_at')->first();
            if ($existingHold && ! $existingHold->hasForfeitedOffer()) {
                return $existingHold;
            }

            if ($resource->hasCopyAvailable()) {
                return $resource->checkouts()->create([
                    'user_id' => $user->id,
                    'checked_out_at' => now(),
                    'due_at' => now()->addDays($resource->checkout_duration_days),
                ]);
            }

            return $resource->holds()->create([
                'user_id' => $user->id,
                'requested_at' => now(),
            ]);
        });
    }

    /**
     * Marks a checkout returned, then promotes the next eligible hold (if
     * any) by giving them a live offer — they still have to claim() it, a
     * return never auto-checks-out on someone's behalf.
     */
    public function return(LibraryCheckout $checkout): void
    {
        DB::transaction(function () use ($checkout) {
            if (! $checkout->isActive()) {
                return;
            }

            $checkout->update(['returned_at' => now()]);

            $resource = $checkout->resource()->lockForUpdate()->first();
            $nextHold = $resource->nextEligibleHold();

            if ($nextHold) {
                $nextHold->update([
                    'notified_at' => now(),
                    'expires_at' => now()->addDays(3),
                ]);
            }
        });
    }

    /** Converts a live hold offer into an actual checkout. */
    public function claim(LibraryHold $hold): LibraryCheckout
    {
        return DB::transaction(function () use ($hold) {
            abort_unless($hold->hasLiveOffer(), 404);

            $resource = $hold->resource()->lockForUpdate()->first();

            $checkout = $resource->checkouts()->create([
                'user_id' => $hold->user_id,
                'checked_out_at' => now(),
                'due_at' => now()->addDays($resource->checkout_duration_days),
            ]);

            $hold->update(['fulfilled_at' => now()]);

            return $checkout;
        });
    }
}
