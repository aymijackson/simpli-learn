<?php

namespace Elibrary\Library\Checkouts;

use App\Models\Tenant;
use App\Models\User;
use App\Notifications\LibraryCopyReady;
use App\Support\SafeNotifier;
use Elibrary\Library\Models\LibraryCheckout;
use Elibrary\Library\Models\LibraryHold;
use Elibrary\Library\Models\LibraryResource;
use Illuminate\Support\Facades\DB;

/**
 * Lending rules, in one place. A copy is either on loan, offered to the
 * person at the front of the waitlist (reserved for them for 3 days), or
 * free. Every change — a loan, a return, a claim, someone leaving the
 * queue, the owner adding copies, an offer running out — ends with
 * offerFreeCopies(), so free copies always go to the waitlist in order
 * before any newcomer can take them.
 */
class LibraryCheckoutService
{
    public const OFFER_DAYS = 3;

    /**
     * Borrow, or join the waitlist. Someone already offered a copy gets it;
     * someone queueing gets one as soon as one is free for them. Never
     * creates a second active loan or a second open hold for a person.
     */
    public function borrow(LibraryResource $resource, User $user): LibraryCheckout|LibraryHold
    {
        return DB::transaction(function () use ($resource, $user) {
            $resource = $this->lock($resource);

            abort_if($resource->needsPurchaseBy($user), 403, 'Purchase this resource before borrowing it.');

            $existingCheckout = $resource->checkouts()->where('user_id', $user->id)->whereNull('returned_at')->first();
            if ($existingCheckout) {
                return $existingCheckout;
            }

            $this->offerFreeCopies($resource);

            $hold = $this->openHoldFor($resource, $user);
            if ($hold?->hasLiveOffer()) {
                return $this->fulfil($resource, $hold);
            }
            if ($hold) {
                return $hold;
            }

            if ($resource->hasCopyAvailable()) {
                return $this->lend($resource, $user->id);
            }

            return $resource->holds()->create(['user_id' => $user->id, 'requested_at' => now()]);
        });
    }

    /** Ends a loan, then offers the freed copy to whoever is next in line. */
    public function return(LibraryCheckout $checkout): void
    {
        DB::transaction(function () use ($checkout) {
            $resource = $this->lock($checkout->resource);
            $checkout->refresh();

            if (! $checkout->isActive()) {
                return;
            }

            $checkout->update(['returned_at' => now()]);
            $this->offerFreeCopies($resource);
        });
    }

    /** Takes up an offered copy. */
    public function claim(LibraryHold $hold): LibraryCheckout
    {
        return DB::transaction(function () use ($hold) {
            $resource = $this->lock($hold->resource);
            $hold->refresh();

            abort_unless($hold->hasLiveOffer(), 404);
            abort_if($resource->needsPurchaseBy($hold->user), 403);

            return $this->fulfil($resource, $hold);
        });
    }

    /** Leaves the waitlist (or turns down an offer, which passes it on). */
    public function cancel(LibraryHold $hold): void
    {
        DB::transaction(function () use ($hold) {
            $resource = $this->lock($hold->resource);
            $hold->delete();
            $this->offerFreeCopies($resource);
        });
    }

    /**
     * Offers every free copy to the waitlist, oldest request first, and
     * tells each person. Offers that ran out unclaimed no longer reserve a
     * copy, so this also passes them on. Safe to call at any time.
     *
     * @return int how many people were offered a copy
     */
    public function offerFreeCopies(LibraryResource $resource): int
    {
        $offered = 0;
        $free = $resource->unreservedCopies();

        while (($free === null || $free > 0) && ($hold = $resource->nextEligibleHold())) {
            $hold->update(['notified_at' => now(), 'expires_at' => now()->addDays(self::OFFER_DAYS)]);
            $this->notifyOffer($resource, $hold);
            $offered++;
            if ($free !== null) {
                $free--;
            }
        }

        return $offered;
    }

    private function lend(LibraryResource $resource, int $userId): LibraryCheckout
    {
        return $resource->checkouts()->create([
            'user_id' => $userId,
            'checked_out_at' => now(),
            'due_at' => now()->addDays($resource->checkout_duration_days ?: 14),
        ]);
    }

    private function fulfil(LibraryResource $resource, LibraryHold $hold): LibraryCheckout
    {
        $checkout = $this->lend($resource, $hold->user_id);
        $hold->update(['fulfilled_at' => now()]);

        return $checkout;
    }

    /** The person's open hold, ignoring one whose offer ran out (they queue again). */
    private function openHoldFor(LibraryResource $resource, User $user): ?LibraryHold
    {
        $hold = $resource->holds()->where('user_id', $user->id)->whereNull('fulfilled_at')->latest('id')->first();

        if ($hold?->hasForfeitedOffer()) {
            $hold->delete();

            return null;
        }

        return $hold;
    }

    private function lock(LibraryResource $resource): LibraryResource
    {
        return LibraryResource::withoutGlobalScopes()->whereKey($resource->getKey())->lockForUpdate()->firstOrFail();
    }

    private function notifyOffer(LibraryResource $resource, LibraryHold $hold): void
    {
        $tenant = Tenant::find($resource->tenant_id);
        $user = User::withoutGlobalScopes()->find($hold->user_id);

        if ($tenant && $user && ! $user->isDeactivated()) {
            // After the transaction commits, so a rollback never sends a stray email.
            DB::afterCommit(fn () => SafeNotifier::send($user, new LibraryCopyReady($resource, $hold->fresh(), $tenant)));
        }
    }
}
