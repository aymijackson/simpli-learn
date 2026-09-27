<?php

namespace Elibrary\Cbt\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Contracts\SharedAcrossTenants;
use Elibrary\Cbt\Contracts\ExamPlacements;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class QuestionOption extends Model implements SharedAcrossTenants
{
    use BelongsToTenant;

    public function sharedWithTenant(Builder $query, int $tenantId): void
    {
        $ids = app(ExamPlacements::class)->sharedExamIds($tenantId);
        $ids
            ? $query->whereIn($this->qualifyColumn('question_id'), DB::table('questions')->select('id')->whereIn('exam_id', $ids))
            : $query->whereRaw('1 = 0');
    }


    protected $fillable = ['tenant_id', 'question_id', 'option_text', 'is_correct', 'position'];

    protected function casts(): array
    {
        return ['is_correct' => 'boolean'];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
