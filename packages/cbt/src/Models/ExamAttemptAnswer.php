<?php

namespace Elibrary\Cbt\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ExamAttemptAnswer extends Model
{
    use BelongsToTenant;

    protected $table = 'exam_attempt_answers';

    protected $fillable = ['tenant_id', 'attempt_id', 'question_id', 'is_flagged'];

    protected function casts(): array
    {
        return ['is_flagged' => 'boolean'];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class, 'attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function selectedOptions(): BelongsToMany
    {
        return $this->belongsToMany(
            QuestionOption::class,
            'exam_attempt_answer_options',
            'answer_id',
            'question_option_id',
        )->withTimestamps();
    }
}
