<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\User;
use App\Notifications\ContactMessageReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->admin = User::factory()->create(['tenant_id' => null]);
    }

    /** A form token as if the page was loaded $secondsAgo. */
    private function token(int $secondsAgo = 30): string
    {
        return Crypt::encryptString((string) (now()->timestamp - $secondsAgo));
    }

    /** Each call comes from its own address unless one is given (the rate limit is per IP). */
    private function send(array $overrides = [], ?string $ip = null)
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip ?? '10.0.'.random_int(0, 255).'.'.random_int(1, 254)])->post('/contact', array_merge([
            'form_token' => $this->token(),
            'name' => 'Tola Visitor',
            'email' => 'Tola@Example.com',
            'subject' => 'Pricing',
            'message' => 'Hello, how much for 200 learners?',
        ], $overrides));
    }

    public function test_the_page_has_the_form_with_its_protections(): void
    {
        $this->get('/contact')->assertOk()
            ->assertSee('name="form_token"', false)->assertSee('name="website"', false)
            ->assertSee('Send message');
        $this->get('/')->assertSee(route('contact'), false);
    }

    public function test_a_message_is_stored_encrypted_and_admins_are_emailed(): void
    {
        $this->send()->assertRedirect('/contact')->assertSessionHas('status');

        $contact = ContactMessage::first();
        $this->assertSame('Tola Visitor', $contact->name);
        $this->assertSame('tola@example.com', $contact->email);
        $this->assertSame(64, strlen($contact->ip_hash));

        $raw = DB::table('contact_messages')->first();
        foreach (['name', 'email', 'subject', 'message'] as $column) {
            $this->assertStringNotContainsString('Tola', (string) $raw->{$column});
            $this->assertStringNotContainsString('200 learners', (string) $raw->{$column});
        }

        Notification::assertSentTo($this->admin, ContactMessageReceived::class, function (ContactMessageReceived $notification) {
            $mail = $notification->toMail($this->admin);

            return $mail->replyTo === [['tola@example.com', 'Tola Visitor']] && str_contains($mail->subject, 'Pricing');
        });
    }

    public function test_the_honeypot_quietly_drops_bots(): void
    {
        $this->send(['website' => 'http://spam.example'])->assertRedirect('/contact')->assertSessionHas('status');

        $this->assertSame(0, ContactMessage::count());
        Notification::assertNothingSent();
    }

    public function test_forms_sent_back_instantly_are_dropped(): void
    {
        $this->send(['form_token' => $this->token(1)])->assertSessionHas('status');

        $this->assertSame(0, ContactMessage::count());
    }

    public function test_a_missing_tampered_or_expired_token_is_refused(): void
    {
        $this->send(['form_token' => 'nonsense'])->assertSessionHasErrors('message');
        $this->send(['form_token' => ''])->assertSessionHasErrors('message');
        $this->send(['form_token' => $this->token(3 * 60 * 60)])->assertSessionHasErrors('message');

        $this->assertSame(0, ContactMessage::count());
    }

    public function test_link_spam_and_bad_input_are_refused(): void
    {
        $this->send(['message' => 'Buy now https://a.example https://b.example https://c.example www.d.example'])->assertSessionHasErrors('message');
        $this->send(['email' => 'not-an-email'])->assertSessionHasErrors('email');
        $this->send(['message' => 'short'])->assertSessionHasErrors('message');
        $this->send(['name' => str_repeat('x', 101)])->assertSessionHasErrors('name');

        $this->assertSame(0, ContactMessage::count());
    }

    public function test_each_visitor_is_rate_limited(): void
    {
        foreach (range(1, 3) as $i) {
            $this->send(['message' => "Message number {$i} is here."], '203.0.113.9')->assertRedirect();
        }

        $this->send([], '203.0.113.9')->assertStatus(429);
        $this->send([], '203.0.113.10')->assertRedirect(); // someone else is unaffected
        $this->assertSame(4, ContactMessage::count());
    }

    public function test_html_is_shown_as_text_to_the_admin(): void
    {
        $this->send(['message' => '<img src=x onerror=alert(1)> please help']);
        $contact = ContactMessage::first();

        $this->actingAs($this->admin)->get("/admin/contact-messages/{$contact->id}")
            ->assertOk()->assertSee('&lt;img src=x onerror=alert(1)&gt;', false)->assertDontSee('<img src=x', false);
    }

    public function test_admins_read_and_delete_messages(): void
    {
        $this->send();
        $contact = ContactMessage::first();

        $this->actingAs($this->admin)->get('/admin/contact-messages')->assertOk()->assertSee('Pricing')->assertSee('Unread', false);
        $this->actingAs($this->admin)->get("/admin/contact-messages/{$contact->id}")->assertOk()->assertSee('Tola Visitor');
        $this->assertNotNull($contact->fresh()->read_at);

        $this->actingAs($this->admin)->delete("/admin/contact-messages/{$contact->id}")->assertRedirect('/admin/contact-messages');
        $this->assertSame(0, ContactMessage::count());
    }

    public function test_messages_are_deleted_after_a_year(): void
    {
        $this->send();
        $this->travel(13)->months();

        $this->artisan('model:prune', ['--model' => ContactMessage::class])->assertSuccessful();

        $this->assertSame(0, ContactMessage::count());
    }

    public function test_turnstile_is_checked_when_configured(): void
    {
        config(['services.turnstile.site_key' => 'site-key', 'services.turnstile.secret' => 'secret']);

        $this->get('/contact')->assertSee('data-sitekey="site-key"', false)
            ->assertHeader('Content-Security-Policy-Report-Only');
        $this->assertStringContainsString('challenges.cloudflare.com', \App\Http\Middleware\SecurityHeaders::policy());

        Http::fake(['challenges.cloudflare.com/*' => Http::sequence()->push(['success' => false])->push(['success' => true])]);
        $this->send(['cf-turnstile-response' => 'bad'])->assertSessionHasErrors('message');
        $this->assertSame(0, ContactMessage::count());

        $this->send(['cf-turnstile-response' => 'good'])->assertSessionHasNoErrors();
        $this->assertSame(1, ContactMessage::count());
    }
}
