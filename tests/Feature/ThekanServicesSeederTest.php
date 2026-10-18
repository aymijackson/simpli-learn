<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\ThekanServicesSeeder;
use Elibrary\Cbt\Models\Exam;
use Elibrary\Lms\Enums\AssessmentMode;
use Elibrary\Lms\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThekanServicesSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_the_workspace_owner_and_induction_course(): void
    {
        $this->seed(ThekanServicesSeeder::class);

        $tenant = Tenant::where('slug', 'thekan-services')->firstOrFail();
        $this->assertSame('Thekan Services Limited', $tenant->name);

        $owner = User::withoutGlobalScopes()->where('tenant_id', $tenant->id)->sole();
        $this->assertSame('ime.udoh@thekanservices.com', $owner->email);
        $this->assertSame(UserRole::Owner, $owner->role);

        $course = Course::withoutGlobalScopes()->where('tenant_id', $tenant->id)->sole();
        $this->assertTrue($course->is_published);
        $this->assertTrue($course->sequential_lessons);
        $this->assertSame(AssessmentMode::PerModule, $course->assessment_mode);
        $this->assertSame(['Architect'], $course->jobTitles()->withoutGlobalScopes()->pluck('name')->all());

        // Every module ends with a test; the last one is the timed final assessment.
        $modules = $course->modules()->withoutGlobalScopes()->orderBy('position')->get();
        $this->assertCount(7, $modules);
        foreach ($modules as $module) {
            $exam = Exam::withoutGlobalScopes()->findOrFail($module->exam_id);
            $this->assertTrue($exam->is_published);
            $this->assertGreaterThanOrEqual(5, $exam->questions()->withoutGlobalScopes()->count());
            $this->assertTrue($module->lessons()->withoutGlobalScopes()->exists());
        }

        $final = Exam::withoutGlobalScopes()->findOrFail($modules->last()->exam_id);
        $this->assertSame('architect-induction-assessment', $final->slug);
        $this->assertTrue($final->enforce_time_limit);
        $this->assertSame(20, $final->questions()->withoutGlobalScopes()->count());

        // Every question has at least one correct answer.
        $exams = Exam::withoutGlobalScopes()->where('tenant_id', $tenant->id)->get();
        foreach ($exams as $exam) {
            foreach ($exam->questions()->withoutGlobalScopes()->get() as $question) {
                $this->assertTrue($question->options()->withoutGlobalScopes()->where('is_correct', true)->exists(), $question->question_text);
            }
        }
    }

    public function test_it_is_safe_to_run_twice(): void
    {
        $this->seed(ThekanServicesSeeder::class);
        $this->seed(ThekanServicesSeeder::class);

        $tenant = Tenant::where('slug', 'thekan-services')->sole();
        $this->assertSame(1, User::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count());
        $this->assertSame(1, Course::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count());
        $this->assertSame(7, Exam::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count());
    }
}
