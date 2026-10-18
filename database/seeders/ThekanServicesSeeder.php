<?php

namespace Database\Seeders;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\JobTitle;
use App\Models\Tenant;
use App\Models\User;
use Elibrary\Cbt\Models\Exam;
use Elibrary\Lms\Models\Course;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Sets up the Thekan Services Limited workspace with the "Architect
 * Induction: Working at Thekan" course: seven modules on how we deliver
 * projects and how to run tasks, costs and documents in Thekan ERP. Each
 * module ends with a test that must be passed before the next one unlocks;
 * the last module's test is the timed induction assessment.
 *
 *   php artisan db:seed --class=ThekanServicesSeeder --force
 *
 * Safe to re-run: it reuses the workspace and owner if they exist and skips
 * the course if it has already been created. Course content lives in
 * database/seeders/content/thekan_architect_induction.php.
 */
class ThekanServicesSeeder extends Seeder
{
    private const NAME = 'Thekan Services Limited';

    private const SLUG = 'thekan-services';

    private const OWNER_NAME = 'Ime Udoh';

    private const OWNER_EMAIL = 'ime.udoh@thekanservices.com';

    public function run(): void
    {
        DB::transaction(function () {
            $tenant = $this->workspace();
            $this->owner($tenant);
            $this->course($tenant);
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

    private function course(Tenant $tenant): void
    {
        $data = require __DIR__.'/content/thekan_architect_induction.php';

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
                : $this->moduleTest($tenant, $modulePosition + 1, $moduleData);

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

        // Recommend the course to architects (and set up the studio's other titles).
        $titles = collect($data['job_titles'])->map(fn ($name) => JobTitle::findOrCreateByName($name, $tenant->id));
        $course->jobTitles()->sync([$titles->first()->id]);

        $this->say(sprintf(
            'Created course "%s": %d modules, %d lessons, %d module tests including the final assessment',
            $course->title,
            count($data['modules']),
            $lessonPosition,
            count($data['modules']),
        ));
        $this->say('Job titles: '.$titles->pluck('name')->implode(', ').' (course recommended for '.$titles->first()->name.')');
    }

    private function moduleTest(Tenant $tenant, int $number, array $moduleData): Exam
    {
        $topic = trim(Str::after($moduleData['title'], ':'));

        $exam = Exam::create([
            'tenant_id' => $tenant->id,
            'title' => "Module {$number} Test: {$topic}",
            'slug' => "induction-module-{$number}-test",
            'description' => "<p>The test for Module {$number} of the Thekan architect induction. Pass it to unlock the next module — you can retake it.</p>",
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

    private function say(string $message): void
    {
        $this->command?->info($message);
    }
}
