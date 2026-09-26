<?php

namespace Elibrary\Cbt\Contracts;

use App\Models\User;
use Elibrary\Cbt\Models\Exam;

/**
 * Lets another module (the LMS) place exams inside its own content — a
 * checkpoint quiz after a lesson, a module test, a course final. Placed
 * exams stop appearing as stand-alone exams, can only be taken from where
 * they sit, and send the learner back there afterwards.
 */
interface ExamPlacements
{
    /** @return array<int, int> Ids of exams that live inside other content. */
    public function embeddedExamIds(): array;

    /** Why this person can't take the exam yet because of where it sits, or null. */
    public function accessBlockReason(Exam $exam, User $user): ?string;

    /**
     * Where the exam sits for this person, for the "part of" link on the exam
     * page and the "continue" button after an attempt.
     *
     * @return array{title: string, url: string, checkpoint: string, next_label: string, next_url: string}|null
     */
    public function contextFor(Exam $exam, User $user): ?array;
}
