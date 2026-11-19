<?php

namespace Database\Seeders;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\JobTitle;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\Tenancy;
use Elibrary\Cbt\Models\Exam;
use Elibrary\Lms\Models\Course;
use Elibrary\Lms\Models\LearningPath;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Sets up the Thekan Services Limited workspace and its training courses:
 *
 *  - Architect Induction: Working at Thekan
 *  - Thekan ERP Foundations (everyone, taken first)
 *  - Projects, Site Installation & Construction in Thekan ERP
 *
 * Each module ends with a test that must be passed before the next one
 * unlocks; the last module's test is the course's timed final assessment.
 * Learning paths put the ERP courses in order, so Foundations must be
 * passed before the module courses open.
 *
 *   php artisan db:seed --class=ThekanServicesSeeder --force
 *
 * Safe to re-run: it reuses the workspace and owner if they exist, skips
 * any course that has already been created, and only adds missing paths.
 * Course content lives in database/seeders/content/thekan_*.php.
 */
class ThekanServicesSeeder extends Seeder
{
    private const NAME = 'Thekan Services Limited';

    private const SLUG = 'thekan-services';

    private const OWNER_NAME = 'Ime Udoh';

    private const OWNER_EMAIL = 'ime.udoh@thekanservices.com';

    /** Content files, in the order the courses are created. */
    private const COURSES = [
        'thekan_architect_induction.php',
        'thekan_erp_foundations.php',
        'thekan_erp_projects.php',
        'thekan_erp_sales.php',
        'thekan_erp_crm.php',
    ];

    /** Job titles used across the firm, so learning can be recommended by role. */
    private const JOB_TITLES = [
        'Architect',
        'Interior Designer',
        'Project Manager',
        'Site Supervisor',
        'Site Engineer',
        'Installation Supervisor',
        'Quantity Surveyor',
        'Estimator',
        'Sales Executive',
        'Procurement Officer',
        'Storekeeper',
        'Production Supervisor',
        'Accountant',
        'HR Officer',
        'ERP Administrator',
    ];

    /** Learning paths: title => [slug, description, course slugs in order]. */
    private const PATHS = [
        'Project & Site Delivery' => [
            'project-site-delivery',
            'For project managers, site and installation supervisors, site engineers, quantity surveyors and architects: Thekan ERP Foundations, then running projects, site installation and construction in the ERP.',
            ['thekan-erp-foundations', 'thekan-erp-projects'],
        ],
        'Estimating & Sales' => [
            'estimating-sales',
            'For estimators, sales executives and quantity surveyors: Thekan ERP Foundations, then estimating, quoting and order-to-cash in the ERP.',
            ['thekan-erp-foundations', 'thekan-erp-sales'],
        ],
        'Client Relationships' => [
            'client-relationships',
            'For everyone who works with clients: Thekan ERP Foundations, then leads, the pipeline, follow-up and customer care in the ERP.',
            ['thekan-erp-foundations', 'thekan-erp-crm'],
        ],
    ];

    public function run(): void
    {
        DB::transaction(function () {
            $tenant = $this->workspace();
            $this->owner($tenant);

            foreach (self::JOB_TITLES as $name) {
                JobTitle::findOrCreateByName($name, $tenant->id);
            }

            foreach (self::COURSES as $file) {
                $this->course($tenant, require __DIR__.'/content/'.$file);
            }

            $this->paths($tenant);
        });
    }

    private function workspace(): Tenant
    {
        $tenant = Tenant::firstOrCreate(
            ['slug' => self::SLUG],
            ['name' => self::NAME, 'status' => TenantStatus::Active],
        );

        foreach ([Module::Lms, Module::Cbt, Module::Library] as $module) {
            $tenant->tenantModules()->updateOrCreate(['module' => $module], ['is_enabled' => true]);
        }

        $this->say($tenant->wasRecentlyCreated
            ? "Created workspace {$tenant->name} at /t/{$tenant->slug}"
            : "Workspace {$tenant->name} already exists — reusing it");

        return $tenant;
    }

    private function owner(Tenant $tenant): void
    {
        $existing = User::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('email', self::OWNER_EMAIL)
            ->first();

        if ($existing) {
            $this->say('Owner '.self::OWNER_EMAIL.' already exists — password left unchanged');

            return;
        }

        $password = Str::password(16, symbols: false);

        User::forceCreate([
            'tenant_id' => $tenant->id,
            'role' => UserRole::Owner,
            'name' => self::OWNER_NAME,
            'email' => self::OWNER_EMAIL,
            'email_verified_at' => now(),
            'password' => $password,
        ]);

        $this->say('Created owner '.self::OWNER_EMAIL);
        $this->command?->warn("  Temporary password: {$password}");
        $this->command?->warn('  This is shown only once. Log in and change it straight away.');
    }

    private function course(Tenant $tenant, array $data): void
    {
        $exists = Course::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('slug', $data['course']['slug'])
            ->exists();

        if ($exists) {
            $this->say("Course \"{$data['course']['title']}\" already exists — skipped (edit it in Manage > Courses)");

            return;
        }

        $course = Course::create([
            'tenant_id' => $tenant->id,
            'title' => $data['course']['title'],
            'slug' => $data['course']['slug'],
            'description' => $data['course']['description'],
            'is_published' => true,
            'assessment_mode' => 'per_module',
            'sequential_lessons' => true,
            'pricing_policy' => 'free',
            'certificate_policy' => 'free',
            ...$data['course']['catalog'],
        ]);

        $lessonPosition = 0;

        foreach ($data['modules'] as $modulePosition => $moduleData) {
            $exam = isset($moduleData['final'])
                ? $this->finalExam($tenant, $moduleData['final'])
                : $this->moduleTest($tenant, $data, $modulePosition + 1, $moduleData);

            $module = $course->modules()->create([
                'tenant_id' => $tenant->id,
                'title' => $moduleData['title'],
                'position' => $modulePosition,
                'exam_id' => $exam->id,
            ]);

            foreach ($moduleData['lessons'] as $lessonData) {
                $course->lessons()->create([
                    'tenant_id' => $tenant->id,
                    'course_module_id' => $module->id,
                    'title' => $lessonData['title'],
                    'content' => $lessonData['content'],
                    'position' => $lessonPosition++,
                    'is_preview' => $lessonData['preview'] ?? false,
                ]);
            }
        }

        // Recommend the course to the job titles it is written for.
        $titles = collect($data['recommended_for'])->map(fn ($name) => JobTitle::findOrCreateByName($name, $tenant->id));
        $course->jobTitles()->sync($titles->pluck('id')->all());

        $this->say(sprintf(
            'Created course "%s": %d modules, %d lessons, %d module tests including the final assessment',
            $course->title,
            count($data['modules']),
            $lessonPosition,
            count($data['modules']),
        ));
        $this->say('  Recommended for: '.$titles->pluck('name')->implode(', '));
    }

    private function moduleTest(Tenant $tenant, array $data, int $number, array $moduleData): Exam
    {
        $topic = trim(Str::after($moduleData['title'], ':'));
        $prefix = $data['test_prefix'];

        $exam = Exam::create([
            'tenant_id' => $tenant->id,
            'title' => ltrim("{$data['test_label']} Module {$number} Test: {$topic}"),
            'slug' => "{$prefix}-module-{$number}-test",
            'description' => "<p>The test for Module {$number} of {$data['course']['title']}. Pass it to unlock the next module — you can retake it.</p>",
            'duration_minutes' => 10,
            'enforce_time_limit' => false,
            'pass_percentage' => 70,
            'is_published' => true,
            'allow_retakes' => true,
            'randomize_questions' => true,
            'certificate_policy' => 'none',
        ]);

        $this->addQuestions($tenant, $exam, $moduleData['test']);

        return $exam;
    }

    private function finalExam(Tenant $tenant, array $finalData): Exam
    {
        $exam = Exam::create([
            'tenant_id' => $tenant->id,
            'title' => $finalData['title'],
            'slug' => $finalData['slug'],
            'description' => $finalData['description'],
            'instructions' => $finalData['instructions'],
            'duration_minutes' => $finalData['duration_minutes'],
            'enforce_time_limit' => true,
            'integrity_monitoring_enabled' => true,
            'pass_percentage' => $finalData['pass_percentage'],
            'is_published' => true,
            'allow_retakes' => true,
            'max_attempts' => $finalData['max_attempts'],
            'randomize_questions' => true,
            // The course issues the certificate once this exam is passed.
            'certificate_policy' => 'none',
        ]);

        $this->addQuestions($tenant, $exam, $finalData['questions']);

        return $exam;
    }

    /**
     * @return int the next free question position
     */
    private function addQuestions(Tenant $tenant, Exam $exam, array $questions, ?int $sectionId = null, int $position = 0): int
    {
        foreach ($questions as $questionData) {
            $correctCount = count(array_filter($questionData['options']));

            $question = $exam->questions()->create([
                'tenant_id' => $tenant->id,
                'exam_section_id' => $sectionId,
                'question_text' => $questionData['q'],
                'answer_type' => $correctCount > 1 ? 'multiple' : 'single',
                'scoring_method' => 'all_or_nothing',
                'points' => 1,
                'position' => $position++,
            ]);

            $optionPosition = 0;

            foreach ($questionData['options'] as $optionText => $isCorrect) {
                $question->options()->create([
                    'tenant_id' => $tenant->id,
                    'option_text' => $optionText,
                    'is_correct' => $isCorrect,
                    'position' => $optionPosition++,
                ]);
            }
        }

        return $position;
    }

    /**
     * Ordered learning paths. A sequential path makes each course require
     * the one before it, so Foundations unlocks the module courses.
     */
    private function paths(Tenant $tenant): void
    {
        foreach (self::PATHS as $title => [$slug, $description, $courseSlugs]) {
            if (LearningPath::withoutGlobalScopes()->where('tenant_id', $tenant->id)->where('slug', $slug)->exists()) {
                $this->say("Learning path \"{$title}\" already exists — skipped");

                continue;
            }

            $courseIds = collect($courseSlugs)->map(fn ($courseSlug) => Course::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('slug', $courseSlug)
                ->value('id'));

            if ($courseIds->contains(null)) {
                $this->command?->warn("Learning path \"{$title}\" skipped — one of its courses is missing");

                continue;
            }

            $path = LearningPath::create([
                'tenant_id' => $tenant->id,
                'title' => $title,
                'slug' => $slug,
                'description' => $description,
                'is_published' => true,
                'is_sequential' => true,
            ]);
            // syncCourses() looks the courses up through the tenant scope, so
            // run it inside this workspace (and put the context back after).
            $tenancy = app(Tenancy::class);
            $previous = $tenancy->current();
            $tenancy->set($tenant);
            try {
                $path->syncCourses($courseIds->all());
            } finally {
                $previous ? $tenancy->set($previous) : $tenancy->forget();
            }

            $this->say("Created learning path \"{$title}\" (".count($courseSlugs).' courses, in order)');
        }
    }

    private function say(string $message): void
    {
        $this->command?->info($message);
    }
}
