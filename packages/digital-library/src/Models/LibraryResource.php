<?php

namespace Elibrary\Library\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\User;
use Elibrary\Library\Enums\LibraryPricingPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LibraryResource extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'title', 'slug', 'author', 'category', 'description', 'external_url', 'is_published',
        'isbn', 'publisher', 'publication_year', 'language', 'cover_image_path', 'requires_checkout',
        'total_copies', 'checkout_duration_days', 'pricing_policy', 'price', 'currency',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'publication_year' => 'integer',
            'requires_checkout' => 'boolean',
            'total_copies' => 'integer',
            'checkout_duration_days' => 'integer',
            'pricing_policy' => LibraryPricingPolicy::class,
        ];
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ResourceTag::class, 'library_resource_tag', 'resource_id', 'tag_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(LibraryResourceFile::class, 'resource_id')->orderBy('position');
    }

    public function checkouts(): HasMany
    {
        return $this->hasMany(LibraryCheckout::class, 'resource_id');
    }

    public function holds(): HasMany
    {
        return $this->hasMany(LibraryHold::class, 'resource_id');
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(LibraryResourcePurchase::class, 'resource_id');
    }

    public function isPurchasedBy(User $user): bool
    {
        return $this->purchases()->where('user_id', $user->id)->where('status', 'paid')->exists();
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(LibraryResourceRating::class, 'resource_id');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(LibraryFavorite::class, 'resource_id');
    }

    public function averageRating(): ?float
    {
        $average = $this->ratings()->avg('stars');

        return $average !== null ? round($average, 1) : null;
    }

    public function ratingsCount(): int
    {
        return $this->ratings()->count();
    }

    public function ratingFor(User $user): ?LibraryResourceRating
    {
        return $this->ratings()->where('user_id', $user->id)->first();
    }

    public function isFavoritedBy(User $user): bool
    {
        return $this->favorites()->where('user_id', $user->id)->exists();
    }

    public function isPaid(): bool
    {
        return $this->pricing_policy === LibraryPricingPolicy::Paid;
    }

    /** A paid resource this person still has to buy before anything else. */
    public function needsPurchaseBy(User $user): bool
    {
        return $this->isPaid() && ! $user->isOwner() && ! $this->isPurchasedBy($user);
    }

    /**
     * The single access predicate used everywhere a file, the reader or the
     * external link decides whether a person can get to the content. Owners
     * always can. A paid resource must be bought first — whether or not it
     * is also lent out — and one that is lent out needs an active checkout.
     */
    public function isAccessibleTo(User $user): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if ($this->needsPurchaseBy($user)) {
            return false;
        }

        return ! $this->requires_checkout || $this->hasActiveCheckoutFor($user);
    }

    /** A card payment started but not yet confirmed, or a bank transfer awaiting the owner. */
    public function pendingPurchaseFor(User $user): ?LibraryResourcePurchase
    {
        return $this->purchases()->where('user_id', $user->id)->where('status', 'pending')->latest('id')->first();
    }

    /** A loan that is still running — access ends at the due date. */
    public function hasActiveCheckoutFor(User $user): bool
    {
        return $this->checkouts()->where('user_id', $user->id)->whereNull('returned_at')->where('due_at', '>', now())->exists();
    }

    public function activeCheckoutsCount(): int
    {
        return $this->checkouts()->whereNull('returned_at')->count();
    }

    /** Holds currently offered a copy — that copy is reserved for them. */
    public function liveOffersCount(): int
    {
        return $this->holds()->whereNull('fulfilled_at')->whereNotNull('notified_at')->where('expires_at', '>', now())->count();
    }

    /** People still queueing, not yet offered a copy. */
    public function waitingHoldsCount(): int
    {
        return $this->holds()->whereNull('fulfilled_at')->whereNull('notified_at')->count();
    }

    /**
     * Copies nobody is borrowing and nobody has been offered. Used by
     * LibraryCheckoutService to offer copies to the waitlist in order.
     */
    public function unreservedCopies(): ?int
    {
        if ($this->total_copies === null) {
            return null;
        }

        return max(0, $this->total_copies - $this->activeCheckoutsCount() - $this->liveOffersCount());
    }

    /**
     * Copies a newcomer could take right now: unreserved and not owed to
     * anyone already queueing. Null = unlimited (loans tracked, no scarcity).
     */
    public function availableCopies(): ?int
    {
        $unreserved = $this->unreservedCopies();

        return $unreserved === null ? null : max(0, $unreserved - $this->waitingHoldsCount());
    }

    public function hasCopyAvailable(): bool
    {
        $available = $this->availableCopies();

        return $available === null || $available > 0;
    }

    /**
     * The earliest hold not yet offered a copy — the next in line. Holds
     * with a live or lapsed offer keep `notified_at`, so they're skipped;
     * LibraryCheckoutService::offerFreeCopies() does the offering.
     */
    public function nextEligibleHold(): ?LibraryHold
    {
        return $this->holds()
            ->whereNull('fulfilled_at')
            ->whereNull('notified_at')
            ->orderBy('requested_at')
            ->first();
    }
}
