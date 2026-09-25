<?php

namespace Elibrary\Lms\Certificates;

use App\Models\User;
use Elibrary\Lms\Enums\CourseCertificatePolicy;
use Elibrary\Lms\Models\Course;
use Elibrary\Lms\Models\CourseCertificate;

class CourseCertificateService
{
    /**
     * Idempotent — safe to call on every course view / lesson completion,
     * since Course::isPassedBy() is a live-computed property with no stored
     * "completion" event to hook a single trigger to (and a CourseFinal-mode
     * pass happens inside the CBT package, which must never depend back on
     * LMS). No-ops if already issued, not yet passed, or the policy isn't
     * "free". "paid" issues nothing here — that's CoursePurchase's job once
     * a purchase_type=certificate payment confirms.
     */
    public function issueIfPassedAndFree(Course $course, User $user): void
    {
        if ($course->certificate_policy !== CourseCertificatePolicy::Free) {
            return;
        }

        if (! $course->isPassedBy($user) || $course->certificateFor($user)) {
            return;
        }

        CourseCertificate::issueFor($course, $user);
    }
}
