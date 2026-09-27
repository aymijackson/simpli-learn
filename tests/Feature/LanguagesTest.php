<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\CourseAssigned;
use Elibrary\Lms\Models\Course;
use Elibrary\Lms\Models\CourseAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LanguagesTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $learner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        $this->tenant->tenantModules()->create(['module' => Module::Lms, 'is_enabled' => true]);
        $this->learner = User::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Ada Learner']);
    }

    public function test_english_is_the_default(): void
    {
        $this->actingAs($this->learner)->get('/t/acme')
            ->assertOk()->assertSee('<html lang="en"', false)->assertSee('My learning');
    }

    public function test_the_workspace_default_applies_to_its_learners(): void
    {
        $this->tenant->update(['locale' => 'fr']);

        $this->actingAs($this->learner)->get('/t/acme')
            ->assertOk()->assertSee('<html lang="fr"', false)->assertSee('Mes cours')->assertDontSee('My learning');
    }

    public function test_a_personal_choice_beats_the_workspace_default(): void
    {
        $this->tenant->update(['locale' => 'fr']);
        $this->learner->update(['locale' => 'yo']);

        $this->actingAs($this->learner)->get('/t/acme')
            ->assertOk()->assertSee('<html lang="yo"', false)->assertSee('Ẹ̀kọ́ mi');
    }

    public function test_the_sign_in_page_can_switch_language_for_the_session(): void
    {
        $this->get('/t/acme/login?lang=ha')->assertOk()->assertSee('<html lang="ha"', false)->assertSee('Kalmar sirri');
        // Remembered for the rest of the visit.
        $this->get('/t/acme/login')->assertOk()->assertSee('<html lang="ha"', false);
    }

    public function test_unknown_languages_are_ignored(): void
    {
        $this->get('/t/acme/login?lang=xx')->assertOk()->assertSee('<html lang="en"', false);
    }

    public function test_learners_can_pick_their_language_on_their_profile(): void
    {
        $this->actingAs($this->learner)->put('/t/acme/profile', ['name' => 'Ada Learner', 'email' => $this->learner->email, 'locale' => 'pcm'])
            ->assertSessionHasNoErrors();
        $this->assertSame('pcm', $this->learner->fresh()->locale);

        $this->actingAs($this->learner)->put('/t/acme/profile', ['name' => 'Ada Learner', 'email' => $this->learner->email, 'locale' => 'xx'])
            ->assertSessionHasErrorsIn('details', 'locale');
    }

    public function test_owners_set_the_workspace_default(): void
    {
        $owner = User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => UserRole::Owner]);

        $this->actingAs($owner)
            ->put('/t/acme/settings', ['name' => 'Acme', 'locale' => 'ig']);

        $this->assertSame('ig', $this->tenant->fresh()->locale);
    }

    public function test_notifications_use_the_recipients_language(): void
    {
        $this->tenant->update(['locale' => 'fr']);
        $course = Course::create(['tenant_id' => $this->tenant->id, 'title' => 'Bases', 'slug' => 'bases', 'is_published' => true]);
        $assignment = CourseAssignment::create(['tenant_id' => $this->tenant->id, 'course_id' => $course->id, 'user_id' => $this->learner->id]);

        $this->learner->fresh()->notify(new CourseAssigned($assignment->setRelation('course', $course), $this->tenant, 'Olu'));

        $stored = $this->learner->notifications()->first()->data;
        $this->assertSame('Nouveau cours attribué', $stored['title']);
        $this->assertSame('Olu vous a attribué Bases.', $stored['body']);
        // The request's own language is left alone.
        $this->assertSame('en', app()->getLocale());
    }

    public function test_notification_emails_use_the_recipients_language(): void
    {
        Notification::fake();
        $this->learner->update(['locale' => 'pcm']);

        $this->assertSame('pcm', $this->learner->fresh()->preferredLocale());
        $this->tenant->update(['locale' => 'fr']);
        $this->assertSame('fr', User::factory()->create(['tenant_id' => $this->tenant->id])->preferredLocale());
    }

    public function test_every_language_file_covers_the_same_keys_and_placeholders(): void
    {
        $reference = json_decode(file_get_contents(lang_path('fr.json')), true);
        $placeholders = fn (string $text) => collect(preg_match_all('/:[a-z_][a-z0-9_]*/', $text, $m) ? $m[0] : [])->sort()->values()->all();

        foreach (array_keys(config('languages.available')) as $code) {
            if ($code === 'en') {
                continue;
            }
            $lines = json_decode(file_get_contents(lang_path("{$code}.json")), true);
            $this->assertIsArray($lines, "lang/{$code}.json is not valid JSON");
            $this->assertEqualsCanonicalizing(array_keys($reference), array_keys($lines), "lang/{$code}.json keys differ from fr.json");

            foreach ($lines as $key => $text) {
                $this->assertSame($placeholders($key), $placeholders($text), "{$code}: placeholders differ in “{$key}”");
                $this->assertSame(substr_count($key, '|'), substr_count($text, '|'), "{$code}: plural forms differ in “{$key}”");
            }
        }
    }
}
