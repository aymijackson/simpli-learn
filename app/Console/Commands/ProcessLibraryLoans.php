<?php

namespace App\Console\Commands;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Notifications\LibraryLoanDueSoon;
use App\Notifications\LibraryLoanEnded;
use App\Support\SafeNotifier;
use App\Support\Tenancy\Tenancy;
use Elibrary\Library\Checkouts\LibraryCheckoutService;
use Elibrary\Library\Models\LibraryCheckout;
use Elibrary\Library\Models\LibraryHold;
use Elibrary\Library\Models\LibraryResource;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Runs hourly (see routes/console.php). Per workspace:
 *  1. ends loans past their due date, freeing the copy;
 *  2. passes on waitlist offers that ran out unclaimed;
 *  3. offers any free copies to the waitlist;
 *  4. sends one "ending soon" reminder per loan, a day or two ahead.
 */
class ProcessLibraryLoans extends Command
{
    protected $signature = 'library:process-loans';

    protected $description = 'End overdue library loans, move the waitlist along and send due-soon reminders';

    public function handle(Tenancy $tenancy, LibraryCheckoutService $service): int
    {
        $tenantIds = collect()
            ->merge(LibraryCheckout::withoutGlobalScopes()->whereNull('returned_at')->distinct()->pluck('tenant_id'))
            ->merge(LibraryHold::withoutGlobalScopes()->whereNull('fulfilled_at')->distinct()->pluck('tenant_id'))
            ->unique();

        $ended = $reminded = $offered = 0;

        foreach (Tenant::whereIn('id', $tenantIds)->where('status', TenantStatus::Active)->get() as $tenant) {
            $tenancy->set($tenant);

            try {
                foreach (LibraryCheckout::with('resource', 'user')->whereNull('returned_at')->where('due_at', '<=', now())->get() as $checkout) {
                    $service->return($checkout);
                    if ($checkout->user && ! $checkout->user->isDeactivated() && $checkout->resource) {
                        SafeNotifier::send($checkout->user, new LibraryLoanEnded($checkout, $tenant));
                    }
                    $ended++;
                }

                // Offers that ran out: drop them so the copy passes to the next person.
                LibraryHold::whereNull('fulfilled_at')->whereNotNull('notified_at')->where('expires_at', '<=', now())->delete();

                $waitingOn = LibraryHold::whereNull('fulfilled_at')->whereNull('notified_at')->distinct()->pluck('resource_id');
                foreach (LibraryResource::whereIn('id', $waitingOn)->get() as $resource) {
                    $offered += DB::transaction(fn () => $service->offerFreeCopies($resource));
                }

                $dueSoon = LibraryCheckout::with('resource', 'user')
                    ->whereNull('returned_at')->whereNull('reminder_sent_at')
                    ->where('due_at', '>', now())->where('due_at', '<=', now()->addDays(2))
                    // Not for short loans that were only just taken out.
                    ->where('checked_out_at', '<=', now()->subDay())
                    ->get();
                foreach ($dueSoon as $checkout) {
                    if ($checkout->user && ! $checkout->user->isDeactivated() && $checkout->resource
                        && SafeNotifier::send($checkout->user, new LibraryLoanDueSoon($checkout, $tenant))) {
                        $checkout->forceFill(['reminder_sent_at' => now()])->save();
                        $reminded++;
                    }
                }
            } finally {
                $tenancy->forget();
            }
        }

        $this->info("Ended {$ended} ".str('loan')->plural($ended).", offered {$offered} ".str('copy')->plural($offered).", sent {$reminded} ".str('reminder')->plural($reminded).'.');

        return self::SUCCESS;
    }
}
