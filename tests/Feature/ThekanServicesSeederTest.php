<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\JobTitle;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\ThekanServicesSeeder;
use Elibrary\Cbt\Models\Exam;
use Elibrary\Lms\Enums\AssessmentMode;
use Elibrary\Lms\Models\Course;
use Elibrary\Lms\Models\LearningPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThekanServicesSeederTest extends TestCase
{
    use RefreshDatabase;

    /** slug => [modules, final exam slug, final question count] */
    private const COURSES = [
        'architect-induction' => [7, 'architect-induction-assessment', 20],
        'thekan-erp-foundations' => [6, 'erp-foundations-assessment', 20],
        'thekan-erp-projects' => [7, 'erp-projects-assessment', 25],
        'thekan-erp-sales' => [7, 'erp-sales-assessment', 25],
        'thekan-erp-crm' => [6, 'erp-crm-assessment', 20],
        'thekan-erp-procurement' => [6, 'erp-procurement-assessment', 20],
        'thekan-erp-inventory' => [6, 'erp-inventory-assessment', 20],
        'thekan-erp-production' => [6, 'erp-production-assessment', 20],
    ];

    /** path slug => course slugs in order */
    private const PATHS = [
        'project-site-delivery' => ['thekan-erp-foundations', 'thekan-erp-projects'],
        'estimating-sales' => ['thekan-erp-foundations', 'thekan-erp-sales'],
        'client-relationships' => ['thekan-erp-foundations', 'thekan-erp-crm'],
        'procurement' => ['thekan-erp-foundations', 'thekan-erp-procurement'],
        'stores-inventory' => ['thekan-erp-foundations', 'thekan-erp-inventory'],
        'workshop-production' => ['thekan-erp-foundations', 'thekan-erp-production'],
    ];

    public function test_it_creates_the_workspace_owner_and_courses(): void
    {
        $this->seed(ThekanServicesSeeder::class);

        $tenant = Tenant::where('slug', 'thekan-services')->firstOrFail();
        $this->assertSame('Thekan Services Limited', $tenant->name);

        $owner = User::withoutGlobalScopes()->where('tenant_id', $tenant->id)->sole();
        $this->assertSame('ime.udoh@thekanservices.com', $owner->email);
        $this->assertSame(UserRole::Owner, $owner->role);

        $this->assertSame(15, JobTitle::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count());

        foreach (self::COURSES as $slug => [$moduleCount, $finalSlug, $finalQuestions]) {
            $course = Course::withoutGlobalScopes()->where('tenant_id', $tenant->id)->where('slug', $slug)->sole();
            $this->assertTrue($course->is_published);
            $this->assertTrue($course->sequential_lessons);
            $this->assertSame(AssessmentMode::PerModule, $course->assessment_mode);

            // Every module ends with a test; the last one is the timed final assessment.
            $modules = $course->modules()->withoutGlobalScopes()->orderBy('position')->get();
            $this->assertCount($moduleCount, $modules, $slug);
            foreach ($modules as $module) {
                $exam = Exam::withoutGlobalScopes()->findOrFail($module->exam_id);
                $this->assertTrue($exam->is_published);
                $this->assertGreaterThanOrEqual(5, $exam->questions()->withoutGlobalScopes()->count());
                $this->assertTrue($module->lessons()->withoutGlobalScopes()->exists());
            }

            $final = Exam::withoutGlobalScopes()->findOrFail($modules->last()->exam_id);
            $this->assertSame($finalSlug, $final->slug);
            $this->assertTrue($final->enforce_time_limit);
            $this->assertSame($finalQuestions, $final->questions()->withoutGlobalScopes()->count());
        }

        // The induction keeps its original test names; the ERP courses get their own.
        $this->assertTrue(Exam::withoutGlobalScopes()->where('slug', 'induction-module-1-test')->where('title', 'like', 'Module 1 Test:%')->exists());
        $this->assertTrue(Exam::withoutGlobalScopes()->where('slug', 'erp-projects-module-1-test')->where('title', 'like', 'Projects Module 1 Test:%')->exists());

        // Recommended by job title.
        $induction = Course::withoutGlobalScopes()->where('slug', 'architect-induction')->sole();
        $this->assertSame(['Architect'], $induction->jobTitles()->withoutGlobalScopes()->pluck('name')->all());
        $foundations = Course::withoutGlobalScopes()->where('slug', 'thekan-erp-foundations')->sole();
        $this->assertCount(15, $foundations->jobTitles()->withoutGlobalScopes()->get());
        $projects = Course::withoutGlobalScopes()->where('slug', 'thekan-erp-projects')->sole();
        $this->assertContains('Site Supervisor', $projects->jobTitles()->withoutGlobalScopes()->pluck('name')->all());

        // Each learning path puts Foundations first and makes it a prerequisite.
        foreach (self::PATHS as $pathSlug => $courseSlugs) {
            $path = LearningPath::withoutGlobalScopes()->where('tenant_id', $tenant->id)->where('slug', $pathSlug)->sole();
            $this->assertTrue($path->is_sequential);
            $this->assertSame($courseSlugs, $path->courses()->withoutGlobalScopes()->get()->pluck('slug')->all());

            $course = Course::withoutGlobalScopes()->where('slug', $courseSlugs[1])->sole();
            $this->assertSame(['thekan-erp-foundations'], $course->prerequisites()->withoutGlobalScopes()->pluck('slug')->unique()->values()->all(), $pathSlug);
        }

        // Every question has at least one correct answer. ERP course questions
        // all have four options — fewer means two options shared the same text
        // and one silently overwrote the other in the content array.
        $exams = Exam::withoutGlobalScopes()->where('tenant_id', $tenant->id)->get();
        foreach ($exams as $exam) {
            foreach ($exam->questions()->withoutGlobalScopes()->get() as $question) {
                $options = $question->options()->withoutGlobalScopes()->get();
                $this->assertTrue($options->contains('is_correct', true), $question->question_text);
                $this->assertGreaterThanOrEqual(str_starts_with($exam->slug, 'erp-') ? 4 : 3, $options->count(), $question->question_text);
            }
        }
    }

    public function test_it_is_safe_to_run_twice(): void
    {
        $this->seed(ThekanServicesSeeder::class);
        $this->seed(ThekanServicesSeeder::class);

        $tenant = Tenant::where('slug', 'thekan-services')->sole();
        $this->assertSame(1, User::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count());
        $this->assertSame(8, Course::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count());
        $this->assertSame(51, Exam::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count());
        $this->assertSame(6, LearningPath::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count());
        $this->assertSame(15, JobTitle::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count());
    }

    public function test_it_adds_new_courses_to_a_workspace_that_already_has_the_induction(): void
    {
        $this->seed(ThekanServicesSeeder::class);
        $tenant = Tenant::where('slug', 'thekan-services')->sole();

        // Simulate the live workspace before the ERP courses existed.
        foreach (['thekan-erp-production', 'thekan-erp-inventory', 'thekan-erp-procurement', 'thekan-erp-crm', 'thekan-erp-sales', 'thekan-erp-projects', 'thekan-erp-foundations'] as $slug) {
            $course = Course::withoutGlobalScopes()->where('slug', $slug)->sole();
            $examIds = $course->modules()->withoutGlobalScopes()->pluck('exam_id');
            LearningPath::withoutGlobalScopes()->where('tenant_id', $tenant->id)->delete();
            $course->delete();
            Exam::withoutGlobalScopes()->whereIn('id', $examIds)->get()->each->delete();
        }

        $this->seed(ThekanServicesSeeder::class);

        $this->assertSame(8, Course::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count());
        $this->assertSame(6, LearningPath::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count());
    }
}
