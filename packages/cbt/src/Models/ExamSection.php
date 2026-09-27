<?php

namespace Elibrary\Cbt\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Contracts\SharedAcrossTenants;
use Elibrary\Cbt\Contracts\ExamPlacements;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamSection extends Model implements SharedAcrossTenants
{
    use BelongsToTenant;

    public function sharedWithTenant(Builder $query, int $tenantId): void
    {
        $ids = app(ExamPlacements::class)->sharedExamIds($tenantId);
        $ids ? $query->whereIn($this->qualifyColumn('exam_id'), $ids) : $query->whereRaw('1 = 0');
    }


    protected $fillable = ['tenant_id', 'exam_id', 'title', 'instructions', 'position'];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('position');
    }
}
