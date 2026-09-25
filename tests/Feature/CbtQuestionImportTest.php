<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Elibrary\Cbt\Models\Exam;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CbtQuestionImportTest extends TestCase
{
    use RefreshDatabase;

    private function tenantWithOwner(string $slug = 'acme'): array
    {
        $tenant = Tenant::create(['name' => ucfirst($slug), 'slug' => $slug, 'status' => TenantStatus::Active]);
        $tenant->tenantModules()->create(['module' => Module::Cbt, 'is_enabled' => true]);
        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::Owner]);

        return [$tenant, $owner];
    }

    private function emptyExam(Tenant $tenant): Exam
    {
        return Exam::create([
            'tenant_id' => $tenant->id,
            'title' => 'Quiz',
            'slug' => 'quiz',
            'duration_minutes' => 30,
            'pass_percentage' => 50,
            'is_published' => true,
        ]);
    }

    private function csvFile(string $contents, string $name = 'questions.csv'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, $contents);
    }

    public function test_a_member_cannot_access_the_import_screens(): void
    {
        [$tenant] = $this->tenantWithOwner();
        $member = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::Member]);
        $exam = $this->emptyExam($tenant);

        $this->actingAs($member)->get("/t/acme/cbt/manage/exams/{$exam->slug}/questions/import")->assertForbidden();
        $this->actingAs($member)->get("/t/acme/cbt/manage/exams/{$exam->slug}/questions/import/template")->assertForbidden();
        $this->actingAs($member)->post("/t/acme/cbt/manage/exams/{$exam->slug}/questions/import")->assertForbidden();
    }

    public function test_the_template_download_has_the_expected_header_row(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $exam = $this->emptyExam($tenant);

        $response = $this->actingAs($owner)->get("/t/acme/cbt/manage/exams/{$exam->slug}/questions/import/template");

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $content = $response->streamedContent();

        $this->assertStringStartsWith('question_text,answer_type,scoring_method,section,points,option_1,correct_1', $content);
        $this->assertStringContainsString('capital of France', $content);
    }

    public function test_a_points_column_overrides_the_default_weight_of_one(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $exam = $this->emptyExam($tenant);

        $csv = "question_text,points,option_1,correct_1,option_2,correct_2\nHigh value,5,A,yes,B,no\n";

        $this->actingAs($owner)->post("/t/acme/cbt/manage/exams/{$exam->slug}/questions/import", [
            'questions_file' => $this->csvFile($csv),
        ])->assertRedirect();

        $this->assertSame(5, $exam->fresh()->questions()->first()->points);
    }

    public function test_a_non_positive_points_value_rejects_the_whole_file(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $exam = $this->emptyExam($tenant);

        $csv = "question_text,points,option_1,correct_1,option_2,correct_2\nBad points,0,A,yes,B,no\n";

        $response = $this->actingAs($owner)->post("/t/acme/cbt/manage/exams/{$exam->slug}/questions/import", [
            'questions_file' => $this->csvFile($csv),
        ]);

        $response->assertSessionHasErrors('questions_file');
        $this->assertSame(0, $exam->fresh()->questions()->count());
    }

    public function test_a_valid_csv_imports_single_and_multiple_answer_questions_with_correct_scoring(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $exam = $this->emptyExam($tenant);

        $csv = "question_text,answer_type,scoring_method,section,option_1,correct_1,option_2,correct_2,option_3,correct_3\n"
            ."What is 2+2?,single,,,3,no,4,yes,5,no\n"
            ."Which are even?,multiple,partial_credit,,2,yes,3,no,4,yes\n";

        $response = $this->actingAs($owner)->post("/t/acme/cbt/manage/exams/{$exam->slug}/questions/import", [
            'questions_file' => $this->csvFile($csv),
        ]);

        $response->assertRedirect(route('cbt.manage.exams.edit', $exam));
        $response->assertSessionHas('status', 'Imported 2 questions from the CSV file.');

        $this->assertSame(2, $exam->fresh()->questions()->count());

        $first = $exam->fresh()->questions()->orderBy('position')->first();
        $this->assertSame('single', $first->answer_type->value);
        $this->assertSame('all_or_nothing', $first->scoring_method->value);
        $this->assertSame(3, $first->options()->count());
        $this->assertSame(1, $first->options()->where('is_correct', true)->count());
        $this->assertTrue($first->options()->where('is_correct', true)->first()->option_text === '4');

        $second = $exam->fresh()->questions()->orderBy('position')->skip(1)->first();
        $this->assertSame('multiple', $second->answer_type->value);
        $this->assertSame('partial_credit', $second->scoring_method->value);
        $this->assertSame(2, $second->options()->where('is_correct', true)->count());
    }

    public function test_imported_questions_are_appended_after_existing_ones(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $exam = $this->emptyExam($tenant);
        $existing = $exam->questions()->create(['tenant_id' => $tenant->id, 'question_text' => 'Existing', 'position' => 0]);
        $existing->options()->create(['tenant_id' => $tenant->id, 'option_text' => 'A', 'is_correct' => true, 'position' => 0]);
        $existing->options()->create(['tenant_id' => $tenant->id, 'option_text' => 'B', 'is_correct' => false, 'position' => 1]);

        $csv = "question_text,option_1,correct_1,option_2,correct_2\nNew question,X,yes,Y,no\n";

        $this->actingAs($owner)->post("/t/acme/cbt/manage/exams/{$exam->slug}/questions/import", [
            'questions_file' => $this->csvFile($csv),
        ])->assertRedirect();

        $imported = $exam->fresh()->questions()->orderBy('position')->skip(1)->first();
        $this->assertSame('New question', $imported->question_text);
        $this->assertSame(1, $imported->position);
    }

    public function test_a_row_naming_an_existing_section_is_assigned_to_it(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $exam = $this->emptyExam($tenant);
        $section = $exam->sections()->create(['tenant_id' => $tenant->id, 'title' => 'Grammar', 'position' => 0]);

        $csv = "question_text,section,option_1,correct_1,option_2,correct_2\nPick the noun,grammar,Cat,yes,Run,no\n";

        $this->actingAs($owner)->post("/t/acme/cbt/manage/exams/{$exam->slug}/questions/import", [
            'questions_file' => $this->csvFile($csv),
        ])->assertRedirect();

        $question = $exam->fresh()->questions()->first();
        $this->assertSame($section->id, $question->exam_section_id);
    }

    public function test_an_unknown_section_name_rejects_the_whole_file_and_imports_nothing(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $exam = $this->emptyExam($tenant);

        $csv = "question_text,section,option_1,correct_1,option_2,correct_2\nPick one,Nonexistent,A,yes,B,no\n";

        $response = $this->actingAs($owner)->post("/t/acme/cbt/manage/exams/{$exam->slug}/questions/import", [
            'questions_file' => $this->csvFile($csv),
        ]);

        $response->assertSessionHasErrors('questions_file');
        $this->assertSame(0, $exam->fresh()->questions()->count());
    }

    public function test_a_single_answer_row_with_two_correct_options_rejects_the_whole_file(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $exam = $this->emptyExam($tenant);

        $csv = "question_text,answer_type,option_1,correct_1,option_2,correct_2\nBad row,single,A,yes,B,yes\n";

        $response = $this->actingAs($owner)->post("/t/acme/cbt/manage/exams/{$exam->slug}/questions/import", [
            'questions_file' => $this->csvFile($csv),
        ]);

        $response->assertSessionHasErrors('questions_file');
        $this->assertSame(0, $exam->fresh()->questions()->count());
    }

    public function test_one_bad_row_blocks_the_import_of_an_otherwise_valid_row(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $exam = $this->emptyExam($tenant);

        $csv = "question_text,option_1,correct_1,option_2,correct_2\n"
            ."Good row,A,yes,B,no\n"
            .",A,yes,B,no\n";

        $response = $this->actingAs($owner)->post("/t/acme/cbt/manage/exams/{$exam->slug}/questions/import", [
            'questions_file' => $this->csvFile($csv),
        ]);

        $response->assertSessionHasErrors('questions_file');
        $this->assertSame(0, $exam->fresh()->questions()->count());
    }

    public function test_missing_required_column_is_rejected_before_any_row_is_read(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $exam = $this->emptyExam($tenant);

        $csv = "answer_type,option_1,correct_1\nsingle,A,yes\n";

        $response = $this->actingAs($owner)->post("/t/acme/cbt/manage/exams/{$exam->slug}/questions/import", [
            'questions_file' => $this->csvFile($csv),
        ]);

        $response->assertSessionHasErrors('questions_file');
        $this->assertSame(0, $exam->fresh()->questions()->count());
    }

    public function test_extra_option_column_pairs_beyond_the_template_default_are_supported(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $exam = $this->emptyExam($tenant);

        $csv = "question_text,option_1,correct_1,option_2,correct_2,option_3,correct_3,option_4,correct_4,option_5,correct_5\n"
            ."Pick the fifth,A,no,B,no,C,no,D,no,E,yes\n";

        $this->actingAs($owner)->post("/t/acme/cbt/manage/exams/{$exam->slug}/questions/import", [
            'questions_file' => $this->csvFile($csv),
        ])->assertRedirect();

        $question = $exam->fresh()->questions()->first();
        $this->assertSame(5, $question->options()->count());
        $this->assertSame('E', $question->options()->where('is_correct', true)->first()->option_text);
    }

    public function test_import_is_scoped_to_the_correct_tenant_and_exam(): void
    {
        [$tenantA, $ownerA] = $this->tenantWithOwner('acme');
        [$tenantB] = $this->tenantWithOwner('other');
        $examA = $this->emptyExam($tenantA);

        $csv = "question_text,option_1,correct_1,option_2,correct_2\nQ1,A,yes,B,no\n";

        $this->actingAs($ownerA)->post("/t/acme/cbt/manage/exams/{$examA->slug}/questions/import", [
            'questions_file' => $this->csvFile($csv),
        ])->assertRedirect();

        $question = $examA->fresh()->questions()->first();
        $this->assertSame($tenantA->id, $question->tenant_id);
        $this->assertNotSame($tenantB->id, $question->tenant_id);
    }
}
