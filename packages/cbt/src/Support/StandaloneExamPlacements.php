<?php

namespace Elibrary\Cbt\Support;

use App\Models\User;
use Elibrary\Cbt\Contracts\ExamPlacements;
use Elibrary\Cbt\Models\Exam;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;

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

    public function markableExamIds(User $user): ?array
    {
        return $user->isOwner() ? null : [];
    }

    public function markersFor(Exam $exam): Collection
    {
        return collect();
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
