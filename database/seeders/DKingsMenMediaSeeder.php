<?php

namespace Database\Seeders;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Elibrary\Cbt\Models\Exam;
use Elibrary\Lms\Models\Course;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Sets up the D'Kings Men Media workspace with the "Data Security &
 * Confidentiality Essentials" course: six modules with knowledge checks
 * and a final assessment that must be passed to complete the course.
 *
 *   php artisan db:seed --class=DKingsMenMediaSeeder --force
 *
 * Safe to re-run: it reuses the workspace and owner if they exist and
 * skips the course if it has already been created. Course content lives
 * in database/seeders/content/dkm_data_security_course.php.
 */
class DKingsMenMediaSeeder extends Seeder
{
    private const NAME = "D'Kings Men Media";

    private const SLUG = 'dkings-men-media';

    private const OWNER_NAME = 'Jackson';

    private const OWNER_EMAIL = 'jackson@dkingsmen.com';

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
        $data = require __DIR__.'/content/dkm_data_security_course.php';

        $exists = Course::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('slug', $data['course']['slug'])
            ->exists();

        if ($exists) {
            $this->say("Course \"{$data['course']['title']}\" already exists — skipped (edit it in Manage > Courses)");

            return;
        }

        $finalExam = $this->finalExam($tenant, $data['final']);

        $course = Course::create([
            'tenant_id' => $tenant->id,
            'title' => $data['course']['title'],
            'slug' => $data['course']['slug'],
            'description' => $data['course']['description'],
            'is_published' => true,
            'assessment_mode' => 'course_final',
            'final_exam_id' => $finalExam->id,
            'pricing_policy' => 'free',
            'certificate_policy' => 'free',
        ]);

        $lessonPosition = 0;

        foreach ($data['modules'] as $modulePosition => $moduleData) {
            $module = $course->modules()->create([
                'tenant_id' => $tenant->id,
                'title' => $moduleData['title'],
                'position' => $modulePosition,
            ]);

            $quiz = $this->knowledgeCheck($tenant, $modulePosition + 1, $moduleData);

            foreach ($moduleData['lessons'] as $index => $lessonData) {
                $content = $lessonData['content'];

                // Point learners at the module's knowledge check from its last lesson.
                if ($index === array_key_last($moduleData['lessons'])) {
                    $url = route('cbt.exams.show', ['tenant' => $tenant->slug, 'exam' => $quiz->slug], absolute: false);
                    $content .= "\n<p><strong>Next:</strong> check your understanding with the <a href=\"{$url}\">{$quiz->title}</a> (5 questions, retake as often as you like).</p>";
                }

                $course->lessons()->create([
                    'tenant_id' => $tenant->id,
                    'course_module_id' => $module->id,
                    'title' => $lessonData['title'],
                    'content' => $content,
                    'position' => $lessonPosition++,
                    'is_preview' => $lessonData['preview'] ?? false,
                ]);
            }
        }

        $this->say(sprintf(
            'Created course "%s": %d modules, %d lessons, %d knowledge checks and the final assessment',
            $course->title,
            count($data['modules']),
            $lessonPosition,
            count($data['modules']),
        ));
    }

    private function knowledgeCheck(Tenant $tenant, int $number, array $moduleData): Exam
    {
        $topic = trim(Str::after($moduleData['title'], ':'));
        $title = "Module {$number} Knowledge Check: {$topic}";

        $exam = Exam::create([
            'tenant_id' => $tenant->id,
            'title' => $title,
            'slug' => Str::slug("module {$number} knowledge check"),
            'description' => "<p>A quick practice check on Module {$number} of Data Security &amp; Confidentiality Essentials. Retake it as often as you like.</p>",
            'duration_minutes' => 10,
            'enforce_time_limit' => false,
            'pass_percentage' => 60,
            'is_published' => true,
            'allow_retakes' => true,
            'randomize_questions' => true,
            'certificate_policy' => 'none',
        ]);

        $this->addQuestions($tenant, $exam, $moduleData['quiz']);

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

        $position = 0;

        foreach ($finalData['sections'] as $sectionPosition => $sectionData) {
            $section = $exam->sections()->create([
                'tenant_id' => $tenant->id,
                'title' => $sectionData['title'],
                'position' => $sectionPosition,
            ]);

            $position = $this->addQuestions($tenant, $exam, $sectionData['questions'], $section->id, $position);
        }

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
