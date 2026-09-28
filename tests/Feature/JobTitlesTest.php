<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\JobTitle;
use App\Models\Tenant;
use App\Models\User;
use Elibrary\Library\Models\LibraryResource;
use Elibrary\Lms\Models\Course;
use Elibrary\Lms\Models\Enrollment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class JobTitlesTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $owner;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'status' => TenantStatus::Active]);
        foreach ([Module::Lms, Module::Library] as $module) {
            $this->tenant->tenantModules()->create(['module' => $module, 'is_enabled' => true]);
        }
        $this->owner = User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => UserRole::Owner]);
        $this->member = User::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Ada Learner']);
    }

    private function title(string $name, ?Tenant $tenant = null): JobTitle
    {
        return JobTitle::findOrCreateByName($name, ($tenant ?? $this->tenant)->id);
    }

    private function course(string $title, array $attributes = []): Course
    {
        return Course::create(array_merge(['tenant_id' => $this->tenant->id, 'title' => $title, 'slug' => str($title)->slug(), 'is_published' => true], $attributes));
    }

    private function resource(string $title): LibraryResource
    {
        return LibraryResource::create(['tenant_id' => $this->tenant->id, 'title' => $title, 'slug' => str($title)->slug(), 'category' => 'General', 'is_published' => true]);
    }

    // --- Titles and duplicates ------------------------------------------------

    public function test_the_same_title_in_any_case_or_spacing_is_one_title(): void
    {
        $a = $this->title('Senior Accountant');
        $b = $this->title('  senior   ACCOUNTANT ');

        $this->assertTrue($a->is($b));
        $this->assertSame('Senior Accountant', $b->name);
        $this->assertSame(1, JobTitle::withoutGlobalScopes()->count());
    }

    public function test_owners_set_a_title_when_adding_someone_and_existing_ones_are_reused(): void
    {
        $this->title('Accountant');

        $this->actingAs($this->owner)->get('/t/acme/team/create')->assertOk()->assertSee('<option value="Accountant">', false);

        $this->actingAs($this->owner)->post('/t/acme/team', ['name' => 'Bola', 'email' => 'bola@example.com', 'role' => 'member', 'job_title' => 'accountant'])->assertRedirect();
        $this->actingAs($this->owner)->post('/t/acme/team', ['name' => 'Chidi', 'email' => 'chidi@example.com', 'role' => 'member', 'job_title' => 'Sales Manager'])->assertRedirect();

        $this->assertSame('Accountant', User::where('email', 'bola@example.com')->first()->jobTitle->name);
        $this->assertSame('Sales Manager', User::where('email', 'chidi@example.com')->first()->jobTitle->name);
        $this->assertSame(2, JobTitle::withoutGlobalScopes()->count());
        $this->actingAs($this->owner)->get('/t/acme/team')->assertSee('Sales Manager');
    }

    public function test_owners_change_or_clear_someones_title_on_their_page(): void
    {
        $this->actingAs($this->owner)->put("/t/acme/team/{$this->member->id}/job-title", ['job_title' => 'Nurse'])->assertRedirect();
        $this->assertSame('Nurse', $this->member->fresh()->jobTitle->name);
        $this->actingAs($this->owner)->get("/t/acme/team/{$this->member->id}")->assertOk()->assertSee('value="Nurse"', false);

        $this->actingAs($this->owner)->put("/t/acme/team/{$this->member->id}/job-title", ['job_title' => ''])->assertRedirect();
        $this->assertNull($this->member->fresh()->job_title_id);
    }

    public function test_learners_pick_an_existing_title_but_cannot_invent_one(): void
    {
        $this->title('Nurse');
        $details = ['name' => 'Ada Learner', 'email' => $this->member->email];

        $this->actingAs($this->member)->get('/t/acme/profile')->assertOk()->assertSee('<option value="Nurse">', false);

        $this->actingAs($this->member)->put('/t/acme/profile', $details + ['job_title' => 'NURSE'])->assertSessionHasNoErrors();
        $this->assertSame('Nurse', $this->member->fresh()->jobTitle->name);

        $this->actingAs($this->member)->put('/t/acme/profile', $details + ['job_title' => 'Astronaut'])->assertSessionHasErrorsIn('details', 'job_title');
        $this->assertSame('Nurse', $this->member->fresh()->jobTitle->name);
        $this->assertNull(JobTitle::findByName('Astronaut', $this->tenant->id));
    }

    public function test_owners_can_add_a_new_title_from_their_profile(): void
    {
        $this->actingAs($this->owner)->put('/t/acme/profile', ['name' => $this->owner->name, 'email' => $this->owner->email, 'job_title' => 'Director'])->assertSessionHasNoErrors();

        $this->assertSame('Director', $this->owner->fresh()->jobTitle->name);
    }

    public function test_a_csv_import_sets_titles_for_new_and_existing_people(): void
    {
        $csv = "name,email,role,Job Title\nZara,zara@example.com,member,Pharmacist\nAda Learner,{$this->member->email},member,pharmacist\n";

        $this->actingAs($this->owner)->post('/t/acme/team/import', ['file' => UploadedFile::fake()->createWithContent('people.csv', $csv)])->assertSessionHasNoErrors();

        $this->assertSame('Pharmacist', User::where('email', 'zara@example.com')->first()->jobTitle->name);
        $this->assertSame('Pharmacist', $this->member->fresh()->jobTitle->name);
        $this->assertSame(1, JobTitle::withoutGlobalScopes()->count());
    }

    // --- Recommending ---------------------------------------------------------------

    public function test_owners_mark_courses_and_resources_as_recommended_for_titles(): void
    {
        $nurse = $this->title('Nurse');
        $course = $this->course('Wound care');
        $resource = $this->resource('Drug handbook');

        $this->actingAs($this->owner)->put('/t/acme/lms/manage/courses/wound-care', [
            'title' => 'Wound care', 'slug' => 'wound-care', 'job_titles_submitted' => '1', 'job_titles' => [$nurse->id], 'new_job_titles' => 'Midwife, nurse',
        ]);
        $this->assertEqualsCanonicalizing(['Nurse', 'Midwife'], $course->fresh()->jobTitles->pluck('name')->all());

        $this->actingAs($this->owner)->put('/t/acme/library/manage/resources/drug-handbook', [
            'title' => 'Drug handbook', 'slug' => 'drug-handbook', 'category' => 'General', 'is_published' => '1',
            'job_titles_submitted' => '1', 'job_titles' => [$nurse->id],
        ])->assertSessionHasNoErrors();
        $this->assertSame(['Nurse'], $resource->fresh()->jobTitles->pluck('name')->all());

        // Unticking everything clears them.
        $this->actingAs($this->owner)->put('/t/acme/library/manage/resources/drug-handbook', [
            'title' => 'Drug handbook', 'slug' => 'drug-handbook', 'category' => 'General', 'is_published' => '1', 'job_titles_submitted' => '1',
        ]);
        $this->assertSame([], $resource->fresh()->jobTitles->pluck('name')->all());
    }

    public function test_another_workspaces_titles_are_ignored(): void
    {
        $other = Tenant::create(['name' => 'Other', 'slug' => 'other', 'status' => TenantStatus::Active]);
        $foreign = $this->title('Spy', $other);
        $course = $this->course('Wound care');

        $this->actingAs($this->owner)->put('/t/acme/lms/manage/courses/wound-care', [
            'title' => 'Wound care', 'slug' => 'wound-care', 'job_titles_submitted' => '1', 'job_titles' => [$foreign->id],
        ]);

        $this->assertSame(0, $course->fresh()->jobTitles()->count());
        $this->actingAs($this->owner)->get('/t/acme/team/create')->assertDontSee('Spy');
    }

    public function test_the_home_page_recommends_for_the_persons_role(): void
    {
        $nurse = $this->title('Nurse');
        $this->member->update(['job_title_id' => $nurse->id]);
        $this->course('Wound care')->jobTitles()->attach($nurse);
        $enrolled = $this->course('Already enrolled');
        $enrolled->jobTitles()->attach($nurse);
        $this->course('For accountants')->jobTitles()->attach($this->title('Accountant'));
        $this->resource('Drug handbook')->jobTitles()->attach($nurse);
        Enrollment::create(['tenant_id' => $this->tenant->id, 'course_id' => $enrolled->id, 'user_id' => $this->member->id]);

        $this->actingAs($this->member)->get('/t/acme')
            ->assertOk()
            ->assertSeeInOrder(['Recommended for your role', 'Picked for Nurse.', 'Wound care', 'Drug handbook'])
            ->assertDontSee('Picked for Accountant');

        $html = $this->actingAs($this->member)->get('/t/acme')->getContent();
        $this->assertSame(1, substr_count($html, '>Wound care<'), 'shown once, not again under general suggestions');
    }

    public function test_no_role_section_without_a_title(): void
    {
        $this->course('Wound care')->jobTitles()->attach($this->title('Nurse'));

        $this->actingAs($this->member)->get('/t/acme')->assertOk()->assertDontSee('Recommended for your role');
        $this->actingAs($this->member)->get('/t/acme/lms')->assertDontSee('For your role');
    }

    public function test_the_catalogues_filter_to_the_persons_role(): void
    {
        $nurse = $this->title('Nurse');
        $this->member->update(['job_title_id' => $nurse->id]);
        $this->course('Wound care')->jobTitles()->attach($nurse);
        $this->course('Bookkeeping');
        $this->resource('Drug handbook')->jobTitles()->attach($nurse);
        $this->resource('Tax tables');

        $this->actingAs($this->member)->get('/t/acme/lms')->assertSee('For your role: Nurse')->assertSee('Bookkeeping');
        $this->actingAs($this->member)->get('/t/acme/lms?for=role')->assertSee('Wound care')->assertDontSee('Bookkeeping');

        $this->actingAs($this->member)->get('/t/acme/library')->assertSee('For your role: Nurse')->assertSee('Tax tables');
        $this->actingAs($this->member)->get('/t/acme/library?for=role')->assertSee('Drug handbook')->assertDontSee('Tax tables');
    }

    // --- Tidying the list ----------------------------------------------------------

    public function test_owners_rename_merge_and_remove_titles(): void
    {
        $rn = $this->title('RN');
        $nurse = $this->title('Nurse');
        $this->member->update(['job_title_id' => $rn->id]);
        $course = $this->course('Wound care');
        $course->jobTitles()->attach($rn);

        $this->actingAs($this->owner)->get('/t/acme/team/job-titles')->assertOk()->assertSee('RN')->assertSee('1 person');

        // Renaming to an existing title merges into it.
        $this->actingAs($this->owner)->put("/t/acme/team/job-titles/{$rn->id}", ['name' => 'nurse'])->assertSessionHas('status', 'Merged “RN” into “Nurse”.');
        $this->assertNull(JobTitle::withoutGlobalScopes()->find($rn->id));
        $this->assertSame($nurse->id, $this->member->fresh()->job_title_id);
        $this->assertSame(['Nurse'], $course->fresh()->jobTitles->pluck('name')->all());

        $this->actingAs($this->owner)->put("/t/acme/team/job-titles/{$nurse->id}", ['name' => 'Registered nurse']);
        $this->assertSame('Registered nurse', $nurse->fresh()->name);

        $this->actingAs($this->owner)->delete("/t/acme/team/job-titles/{$nurse->id}")->assertRedirect();
        $this->assertNull($this->member->fresh()->job_title_id);
        $this->assertSame(0, $course->fresh()->jobTitles()->count());
    }

    public function test_only_owners_manage_titles(): void
    {
        $title = $this->title('Nurse');

        $this->actingAs($this->member)->get('/t/acme/team/job-titles')->assertForbidden();
        $this->actingAs($this->member)->delete("/t/acme/team/job-titles/{$title->id}")->assertForbidden();
        $this->assertNotNull($title->fresh());
    }

    public function test_the_title_is_part_of_personal_data(): void
    {
        $this->member->update(['job_title_id' => $this->title('Nurse')->id]);

        $export = $this->actingAs($this->member)->get('/t/acme/profile/export');
        $this->assertStringContainsString('"job_title": "Nurse"', $export->streamedContent());
    }
}
