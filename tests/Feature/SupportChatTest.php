<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\SupportConversation;
use App\Models\SupportMessage;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\SupportMessageReceived;
use App\Notifications\SupportReplyReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SupportChatTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $owner;

    private User $learner;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $this->tenant->tenantModules()->create(['module' => Module::Lms, 'is_enabled' => true]);
        $this->owner = User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => UserRole::Owner, 'name' => 'Olu Owner']);
        $this->learner = User::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Ada Learner']);
        $this->admin = User::factory()->create(['tenant_id' => null, 'name' => 'Platform Admin']);
    }

    private function ask(User $user, string $body)
    {
        return $this->actingAs($user)->postJson('/t/acme/support/chat', ['body' => $body]);
    }

    private function conversation(User $user): SupportConversation
    {
        return SupportConversation::withoutGlobalScopes()->where('user_id', $user->id)->firstOrFail();
    }

    // --- The bubble ----------------------------------------------------------------

    public function test_the_bubble_shows_for_signed_in_people_with_the_right_desk(): void
    {
        $this->actingAs($this->learner)->get('/t/acme')->assertOk()
            ->assertSee('data-support-bubble', false)->assertSee('Help from Acme')->assertSee('js/support-chat.js', false);

        $this->actingAs($this->owner)->get('/t/acme/manage')->assertOk()->assertSee('Platform support');

        $this->get('/t/acme/login')->assertDontSee('data-support-bubble', false);
    }

    public function test_a_learner_asks_and_the_workspace_owners_are_told(): void
    {
        $this->ask($this->learner, 'How do I reset my exam?')->assertCreated()
            ->assertJsonPath('message.body', 'How do I reset my exam?')->assertJsonPath('message.mine', true);

        $conversation = $this->conversation($this->learner);
        $this->assertSame(SupportConversation::WORKSPACE, $conversation->desk);
        $this->assertSame($this->tenant->id, $conversation->tenant_id);
        Notification::assertSentTo($this->owner, SupportMessageReceived::class, fn ($n, $channels) => $channels === ['database', 'mail']);
        Notification::assertNotSentTo($this->admin, SupportMessageReceived::class);
    }

    public function test_owners_are_emailed_at_most_every_half_hour_per_chat(): void
    {
        $this->ask($this->learner, 'First question');
        $this->ask($this->learner, 'Second question');

        $sent = Notification::sent($this->owner, SupportMessageReceived::class);
        $this->assertCount(2, $sent);
        $this->assertSame(['database', 'mail'], $sent[0]->via($this->owner));
        $this->assertSame(['database'], $sent[1]->via($this->owner));

        $this->travel(31)->minutes();
        $this->ask($this->learner, 'Third question');
        $this->assertSame(['database', 'mail'], Notification::sent($this->owner, SupportMessageReceived::class)[2]->via($this->owner));
    }

    public function test_the_owner_replies_and_the_learner_sees_it_with_an_unread_count(): void
    {
        $this->ask($this->learner, 'Hello?');
        $conversation = $this->conversation($this->learner);

        $this->actingAs($this->owner)->get('/t/acme/manage/support')->assertOk()->assertSee('Ada Learner');
        $this->actingAs($this->owner)->get("/t/acme/manage/support/{$conversation->id}")->assertOk()->assertSee('Hello?');

        $this->actingAs($this->owner)->postJson("/t/acme/manage/support/{$conversation->id}/reply", ['body' => 'Hi Ada, happy to help.'])
            ->assertCreated()->assertJsonPath('message.from_staff', true);
        Notification::assertSentTo($this->learner, SupportReplyReceived::class);

        $this->actingAs($this->learner)->getJson('/t/acme/support/chat')
            ->assertOk()->assertJsonPath('unread', 1)->assertJsonCount(2, 'messages')
            ->assertJsonPath('messages.1.sender', 'Olu Owner')->assertJsonPath('messages.1.mine', false);

        // Opening the panel marks them read; polling after the last id returns nothing new.
        $lastId = SupportMessage::withoutGlobalScopes()->max('id');
        $this->actingAs($this->learner)->getJson('/t/acme/support/chat?read=1')->assertJsonPath('unread', 0);
        $this->actingAs($this->learner)->getJson("/t/acme/support/chat?after={$lastId}")->assertJsonCount(0, 'messages')->assertJsonPath('unread', 0);
    }

    public function test_the_inbox_polls_for_new_messages(): void
    {
        $this->ask($this->learner, 'One');
        $conversation = $this->conversation($this->learner);
        $first = SupportMessage::withoutGlobalScopes()->max('id');
        $this->ask($this->learner, 'Two');

        $this->actingAs($this->owner)->getJson("/t/acme/manage/support/{$conversation->id}/messages?after={$first}")
            ->assertOk()->assertJsonCount(1, 'messages')->assertJsonPath('messages.0.body', 'Two');
    }

    public function test_owners_reach_the_platform_admin(): void
    {
        $this->ask($this->owner, 'Can you enable the library module?')->assertCreated();

        $conversation = $this->conversation($this->owner);
        $this->assertSame(SupportConversation::PLATFORM, $conversation->desk);
        Notification::assertSentTo($this->admin, SupportMessageReceived::class, fn ($n, $channels) => $channels === ['mail']);

        $this->actingAs($this->admin)->get('/admin/support')->assertOk()->assertSee('Olu Owner')->assertSee('Acme');
        $this->actingAs($this->admin)->postJson("/admin/support/{$conversation->id}/reply", ['body' => 'Done — it is on now.'])->assertCreated();

        $this->actingAs($this->owner)->getJson('/t/acme/support/chat?read=1')
            ->assertJsonPath('messages.1.body', 'Done — it is on now.')->assertJsonPath('messages.1.sender', 'Platform support');
        Notification::assertSentTo($this->owner, SupportReplyReceived::class);
    }

    public function test_a_learners_chat_does_not_reach_the_platform_and_vice_versa(): void
    {
        $this->ask($this->learner, 'Learner question');
        $this->ask($this->owner, 'Owner question');
        $learnerChat = $this->conversation($this->learner);
        $ownerChat = $this->conversation($this->owner);

        $this->actingAs($this->admin)->get("/admin/support/{$learnerChat->id}")->assertNotFound();
        $this->actingAs($this->owner)->get("/t/acme/manage/support/{$ownerChat->id}")->assertNotFound();
        $this->actingAs($this->owner)->get('/t/acme/manage/support')->assertSee('Ada Learner')->assertDontSee('Owner question');
    }

    public function test_other_workspaces_and_learners_cannot_read_a_chat(): void
    {
        $this->ask($this->learner, 'Private question');
        $conversation = $this->conversation($this->learner);

        $other = Tenant::create(['name' => 'Other', 'slug' => 'other', 'status' => TenantStatus::Active]);
        $otherOwner = User::factory()->create(['tenant_id' => $other->id, 'role' => UserRole::Owner]);
        $this->actingAs($otherOwner)->get("/t/other/manage/support/{$conversation->id}")->assertNotFound();
        $this->actingAs($otherOwner)->postJson("/t/other/manage/support/{$conversation->id}/reply", ['body' => 'x'])->assertNotFound();

        $classmate = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->actingAs($classmate)->getJson('/t/acme/support/chat')->assertJsonCount(0, 'messages');
        $this->actingAs($classmate)->get('/t/acme/manage/support')->assertForbidden();
        $this->actingAs($this->owner)->get('/admin/support')->assertForbidden();
    }

    public function test_messages_are_plain_text_and_limited(): void
    {
        $this->ask($this->learner, '')->assertUnprocessable();
        $this->ask($this->learner, str_repeat('x', 2001))->assertUnprocessable();

        $this->ask($this->learner, "  <script>alert(1)</script>\u{0007} hi  ")->assertCreated()->assertJsonPath('message.body', '<script>alert(1)</script> hi');

        $conversation = $this->conversation($this->learner);
        $this->actingAs($this->owner)->get("/t/acme/manage/support/{$conversation->id}")
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)', false);
    }

    public function test_sending_is_rate_limited(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            $this->ask($this->learner, "Message {$i}")->assertCreated();
        }

        $this->ask($this->learner, 'One too many')->assertStatus(429);
    }

    public function test_resolving_and_reopening(): void
    {
        $this->ask($this->learner, 'Fixed now?');
        $conversation = $this->conversation($this->learner);

        $this->actingAs($this->owner)->post("/t/acme/manage/support/{$conversation->id}/resolve")->assertRedirect();
        $this->assertSame('resolved', $conversation->fresh()->status);
        $this->actingAs($this->owner)->get('/t/acme/manage/support')->assertDontSee('Ada Learner');
        $this->actingAs($this->owner)->get('/t/acme/manage/support?show=resolved')->assertSee('Ada Learner');

        // Writing again reopens it.
        $this->ask($this->learner, 'Actually, one more thing');
        $this->assertSame('open', $conversation->fresh()->status);
    }

    public function test_the_sidebars_count_chats_waiting_for_a_reply(): void
    {
        $this->ask($this->learner, 'Waiting');
        $this->ask($this->owner, 'Waiting too');

        $this->actingAs($this->owner)->get('/t/acme/manage')->assertSee('Support inbox');
        $this->actingAs($this->admin)->get('/admin')->assertOk()->assertSee('Support chats');

        $conversation = $this->conversation($this->learner);
        $this->actingAs($this->owner)->get("/t/acme/manage/support/{$conversation->id}");
        $this->assertSame(0, SupportConversation::withoutGlobalScopes()->where('desk', 'workspace')->awaitingStaff()->count());
    }
}
