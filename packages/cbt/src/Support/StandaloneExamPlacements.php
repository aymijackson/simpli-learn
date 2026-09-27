<?php

namespace Elibrary\Cbt\Support;

use App\Models\User;
use Elibrary\Cbt\Contracts\ExamPlacements;
use Elibrary\Cbt\Models\Exam;
use Illuminate\Database\Query\Builder;

/** Used when nothing else places exams: every exam stands on its own. */
class StandaloneExamPlacements implements ExamPlacements
{
    public function embeddedExamIds(): array
    {
        return [];
    }

    public function sharedExamIds(int $tenantId): ?Builder
    {
        return null;
    }

    public function accessBlockReason(Exam $exam, User $user): ?string
    {
        return null;
    }

    public function contextFor(Exam $exam, User $user): ?array
    {
        return null;
    }
}
