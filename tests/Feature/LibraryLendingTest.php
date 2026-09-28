<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\LibraryCopyReady;
use App\Notifications\LibraryLoanDueSoon;
use App\Notifications\LibraryLoanEnded;
use Elibrary\Library\Models\LibraryCheckout;
use Elibrary\Library\Models\LibraryHold;
use Elibrary\Library\Models\LibraryResource;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LibraryLendingTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $owner;

    private LibraryResource $book;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $this->tenant->tenantModules()->create(['module' => Module::Library, 'is_enabled' => true]);
        $this->owner = User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => UserRole::Owner]);
        $this->book = LibraryResource::create([
            'tenant_id' => $this->tenant->id, 'title' => 'Rare Book', 'slug' => 'rare-book', 'category' => 'Fiction',
            'is_published' => true, 'requires_checkout' => true, 'total_copies' => 1, 'checkout_duration_days' => 14,
        ]);
    }

    private function member(): User
    {
        return User::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    private function borrow(User $user): void
    {
        $this->actingAs($user)->post('/t/acme/library/resources/rare-book/borrow')->assertRedirect();
    }

    private function loan(User $user): ?LibraryCheckout
    {
        return LibraryCheckout::withoutGlobalScopes()->where('user_id', $user->id)->whereNull('returned_at')->first();
    }

    private function hold(User $user): ?LibraryHold
    {
        return LibraryHold::withoutGlobalScopes()->where('user_id', $user->id)->whereNull('fulfilled_at')->first();
    }

    private function activeLoans(): int
    {
        return LibraryCheckout::withoutGlobalScopes()->whereNull('returned_at')->count();
    }

    public function test_a_returned_copy_is_held_for_the_next_person_and_they_are_told(): void
    {
        [$a, $b, $c] = [$this->member(), $this->member(), $this->member()];
        $this->borrow($a);
        $this->borrow($b);

        $this->actingAs($a)->post('/t/acme/library/checkouts/'.$this->loan($a)->id.'/return');

        $this->assertTrue($this->hold($b)->hasLiveOffer());
        Notification::assertSentTo($b, LibraryCopyReady::class);

        // A newcomer can't take the reserved copy; they queue behind.
        $this->borrow($c);
        $this->assertNull($this->loan($c));
        $this->assertNotNull($this->hold($c));

        $this->actingAs($b)->post('/t/acme/library/holds/'.$this->hold($b)->id.'/claim');
        $this->assertNotNull($this->loan($b));
        $this->assertSame(1, $this->activeLoans());
    }

    public function test_borrowing_again_while_offered_a_copy_takes_it(): void
    {
        [$a, $b] = [$this->member(), $this->member()];
        $this->borrow($a);
        $this->borrow($b);
        $this->actingAs($a)->post('/t/acme/library/checkouts/'.$this->loan($a)->id.'/return');

        $this->borrow($b);

        $this->assertNotNull($this->loan($b));
        $this->assertNull($this->hold($b));
    }

    public function test_added_copies_go_to_the_waitlist_first(): void
    {
        [$a, $b, $c] = [$this->member(), $this->member(), $this->member()];
        $this->borrow($a);
        $this->borrow($b);

        $this->actingAs($this->owner)->put('/t/acme/library/manage/resources/rare-book', [
            'title' => 'Rare Book', 'slug' => 'rare-book', 'category' => 'Fiction', 'is_published' => '1',
            'requires_checkout' => '1', 'total_copies' => 2,
        ])->assertSessionHasNoErrors();

        $this->assertTrue($this->hold($b)->hasLiveOffer());
        Notification::assertSentTo($b, LibraryCopyReady::class);

        $this->borrow($c);
        $this->assertNull($this->loan($c));

        $this->borrow($b);
        $this->assertNotNull($this->loan($b));
        $this->assertSame(2, $this->activeLoans());
    }

    public function test_leaving_the_waitlist_or_turning_down_an_offer_passes_it_on(): void
    {
        [$a, $b, $c] = [$this->member(), $this->member(), $this->member()];
        $this->borrow($a);
        $this->borrow($b);
        $this->borrow($c);
        $this->actingAs($a)->post('/t/acme/library/checkouts/'.$this->loan($a)->id.'/return');
        $this->assertTrue($this->hold($b)->hasLiveOffer());

        $this->actingAs($b)->delete('/t/acme/library/holds/'.$this->hold($b)->id)->assertRedirect();

        $this->assertNull($this->hold($b));
        $this->assertTrue($this->hold($c)->hasLiveOffer());
        Notification::assertSentTo($c, LibraryCopyReady::class);
    }

    public function test_people_can_only_leave_their_own_hold(): void
    {
        [$a, $b] = [$this->member(), $this->member()];
        $this->borrow($a);
        $this->borrow($b);

        $this->actingAs($a)->delete('/t/acme/library/holds/'.$this->hold($b)->id)->assertForbidden();
        $this->assertNotNull($this->hold($b));
    }

    public function test_access_ends_at_the_due_date(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('library-files/1/book.pdf', '%PDF');
        $file = $this->book->files()->create(['tenant_id' => $this->tenant->id, 'title' => 'Text', 'format' => 'pdf', 'access_level' => 'secure', 'disk_path' => 'library-files/1/book.pdf', 'mime_type' => 'application/pdf', 'position' => 0]);
        $a = $this->member();
        $this->borrow($a);
        $url = "/t/acme/library/resources/rare-book/files/{$file->id}/stream";

        $this->assertSame(200, $this->actingAs($a)->get($url)->baseResponse->getStatusCode());

        $this->travel(15)->days();
        $this->actingAs($a)->get($url)->assertForbidden();
        $this->actingAs($a)->get('/t/acme/library/resources/rare-book')->assertSee('Your loan ended on');
    }

    public function test_the_hourly_job_ends_overdue_loans_and_offers_the_copy_on(): void
    {
        [$a, $b] = [$this->member(), $this->member()];
        $this->borrow($a);
        $this->borrow($b);

        $this->travel(15)->days();
        $this->artisan('library:process-loans')->assertSuccessful();

        $this->assertNull($this->loan($a));
        Notification::assertSentTo($a, LibraryLoanEnded::class);
        $this->assertTrue($this->hold($b)->hasLiveOffer());
        Notification::assertSentTo($b, LibraryCopyReady::class);
    }

    public function test_an_unclaimed_offer_passes_to_the_next_person(): void
    {
        [$a, $b, $c] = [$this->member(), $this->member(), $this->member()];
        $this->borrow($a);
        $this->borrow($b);
        $this->borrow($c);
        $this->actingAs($a)->post('/t/acme/library/checkouts/'.$this->loan($a)->id.'/return');

        $this->travel(4)->days();
        $this->artisan('library:process-loans')->assertSuccessful();

        $this->assertNull($this->hold($b));
        $this->assertTrue($this->hold($c)->hasLiveOffer());

        // B can queue again, behind C.
        $this->borrow($b);
        $this->assertNull($this->loan($b));
        $this->assertSame(1, $this->hold($b)->position() - 1);
    }

    public function test_one_due_soon_reminder_per_loan(): void
    {
        $a = $this->member();
        $this->borrow($a);

        $this->travel(12 * 24 + 12)->hours();
        $this->artisan('library:process-loans');
        $this->travel(2)->hours();
        $this->artisan('library:process-loans');

        Notification::assertSentToTimes($a, LibraryLoanDueSoon::class, 1);
        $this->assertNotNull($this->loan($a)->reminder_sent_at);
    }

    public function test_no_reminder_for_a_loan_taken_out_moments_ago(): void
    {
        $this->book->update(['checkout_duration_days' => 1]);
        $a = $this->member();
        $this->borrow($a);

        $this->artisan('library:process-loans');

        Notification::assertNotSentTo($a, LibraryLoanDueSoon::class);
    }

    public function test_the_notifications_read_well(): void
    {
        [$a, $b] = [$this->member(), $this->member()];
        $this->borrow($a);
        $this->borrow($b);
        $this->actingAs($a)->post('/t/acme/library/checkouts/'.$this->loan($a)->id.'/return');

        Notification::assertSentTo($b, LibraryCopyReady::class, function (LibraryCopyReady $notification) use ($b) {
            $data = $notification->toDatabase($b);
            $mail = $notification->toMail($b);

            return $data['title'] === 'A copy is ready for you'
                && str_contains($data['body'], '“Rare Book” is reserved for you until')
                && str_contains($data['url'], '/t/acme/library/resources/rare-book')
                && $mail->actionText === 'Claim your copy';
        });
    }

    public function test_the_job_is_scheduled_hourly(): void
    {
        $event = collect(app(Schedule::class)->events())->first(fn ($event) => str_contains($event->command ?? '', 'library:process-loans'));

        $this->assertNotNull($event);
        $this->assertSame('0 * * * *', $event->expression);
    }
}
