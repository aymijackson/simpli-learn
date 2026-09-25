<?php

namespace Elibrary\Cbt\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Support\Tenancy\Tenancy;
use Elibrary\Cbt\Enums\AnswerType;
use Elibrary\Cbt\Enums\ScoringMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Question extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'exam_id', 'exam_section_id', 'question_text', 'answer_type', 'scoring_method', 'position', 'points'];

    protected function casts(): array
    {
        return [
            'answer_type' => AnswerType::class,
            'scoring_method' => ScoringMethod::class,
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(ExamSection::class, 'exam_section_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('position');
    }

    /**
     * Score a set of selected option ids against this question's correct
     * options, per its configured scoring method. Returns 0.0–1.0. This is
     * the single source of truth for grading, shared by the submit-time
     * controller and the result view so they can never drift apart.
     */
    public function scoreForSelection(Collection $selectedOptionIds): float
    {
        $selectedOptionIds = $selectedOptionIds->map(fn ($id) => (int) $id)->unique();
        $correctOptionIds = $this->options->where('is_correct', true)->pluck('id');

        if ($correctOptionIds->isEmpty()) {
            return 0.0;
        }

        return match ($this->scoring_method) {
            ScoringMethod::AllOrNothing => $selectedOptionIds->diff($correctOptionIds)->isEmpty()
                && $correctOptionIds->diff($selectedOptionIds)->isEmpty()
                ? 1.0 : 0.0,
            ScoringMethod::PartialCredit => max(0.0, (
                $selectedOptionIds->intersect($correctOptionIds)->count()
                - $selectedOptionIds->diff($correctOptionIds)->count()
            ) / $correctOptionIds->count()),
        };
    }

    /**
     * Save (or update) this question's answer for an attempt — the single
     * place that writes an ExamAttemptAnswer, used identically whether the
     * answer arrived all at once (AttemptController::submit) or one page at
     * a time (AttemptQuestionController::answer), so both delivery modes
     * grade from the exact same kind of saved state.
     */
    public function saveAnswerFor(ExamAttempt $attempt, Collection $selectedOptionIds, bool $isFlagged = false): ExamAttemptAnswer
    {
        $tenantId = app(Tenancy::class)->id();
        $validOptionIds = $this->options->pluck('id');
        $selectedOptionIds = $selectedOptionIds->map(fn ($id) => (int) $id)->intersect($validOptionIds);

        $answer = $attempt->answers()->updateOrCreate(
            ['question_id' => $this->id],
            ['is_flagged' => $isFlagged],
        );

        $answer->selectedOptions()->sync(
            $selectedOptionIds->mapWithKeys(fn ($id) => [$id => ['tenant_id' => $tenantId]])->all()
        );

        return $answer;
    }
}
