<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\User;
use Elibrary\Cbt\Models\Certificate;
use Elibrary\Cbt\Models\CertificatePayment;
use Elibrary\Cbt\Models\ExamAttempt;
use Elibrary\Library\Models\LibraryCheckout;
use Elibrary\Library\Models\LibraryFavorite;
use Elibrary\Library\Models\LibraryHold;
use Elibrary\Library\Models\LibraryReadingProgress;
use Elibrary\Library\Models\LibraryResourcePurchase;
use Elibrary\Library\Models\LibraryResourceRating;
use Elibrary\Lms\Models\CourseCertificate;
use Elibrary\Lms\Models\CoursePurchase;
use Elibrary\Lms\Models\Enrollment;
use Elibrary\Lms\Models\LessonProgress;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Data-subject requests under the NDPA: export everything held about a
 * person (right of access / portability) and erase their personal data.
 *
 * Erasure anonymises rather than deletes: every learner table cascades on
 * user deletion, and payment, exam and course records must be kept for
 * financial and legal reasons. Those records stay attached to an account
 * that no longer identifies anyone.
 */
class PersonalData
{
    /** Records exported per person: label => model class. */
    private const RECORDS = [
        'course_enrollments' => Enrollment::class,
        'lesson_progress' => LessonProgress::class,
        'course_purchases' => CoursePurchase::class,
        'course_certificates' => CourseCertificate::class,
        'exam_attempts' => ExamAttempt::class,
        'exam_certificates' => Certificate::class,
        'certificate_payments' => CertificatePayment::class,
        'library_checkouts' => LibraryCheckout::class,
        'library_waitlist' => LibraryHold::class,
        'library_favorites' => LibraryFavorite::class,
        'library_ratings' => LibraryResourceRating::class,
        'library_reading_progress' => LibraryReadingProgress::class,
        'library_purchases' => LibraryResourcePurchase::class,
    ];

    /** Purely personal records removed on erasure. */
    private const DELETE_ON_ERASE = [
        LibraryFavorite::class,
        LibraryResourceRating::class,
        LibraryReadingProgress::class,
        LibraryHold::class,
    ];

    public static function export(User $user): array
    {
        $records = [];

        foreach (self::RECORDS as $label => $model) {
            $records[$label] = $model::withoutGlobalScopes()
                ->where('user_id', $user->id)
                ->get()
                ->map(fn ($row) => $row->makeHidden(['tenant_id', 'user_id'])->toArray())
                ->all();
        }

        return [
            'exported_at' => now()->toIso8601String(),
            'workspace' => $user->tenant?->name,
            'account' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role?->label(),
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                'created_at' => $user->created_at?->toIso8601String(),
            ],
            'records' => $records,
            'activity' => ActivityLog::where('user_id', $user->id)->latest('id')->get()
                ->map(fn (ActivityLog $entry) => [
                    'when' => $entry->created_at->toIso8601String(),
                    'action' => $entry->action,
                    'description' => $entry->description,
                    'ip_address' => $entry->ip_address,
                ])->all(),
        ];
    }

    public static function erase(User $user): void
    {
        DB::transaction(function () use ($user) {
            $oldName = $user->name;
            $oldEmail = $user->email;
            $placeholder = "Erased user #{$user->id}";

            foreach (self::DELETE_ON_ERASE as $model) {
                $model::withoutGlobalScopes()->where('user_id', $user->id)->delete();
            }

            // Hand back any books still on loan so copies aren't stuck.
            LibraryCheckout::withoutGlobalScopes()
                ->where('user_id', $user->id)
                ->whereNull('returned_at')
                ->update(['returned_at' => now()]);

            // Scrub the name and email from the activity trail.
            ActivityLog::where('tenant_id', $user->tenant_id)
                ->where(fn ($query) => $query->where('user_id', $user->id)
                    ->orWhere('description', 'like', '%'.$oldEmail.'%')
                    ->orWhere('description', 'like', '%'.$oldName.'%'))
                ->get()
                ->each(function (ActivityLog $entry) use ($user, $oldName, $oldEmail, $placeholder) {
                    $entry->description = str_replace([$oldEmail, $oldName], ['[erased email]', $placeholder], $entry->description);
                    if ($entry->user_id === $user->id) {
                        $entry->actor_name = $placeholder;
                    }
                    if (str_contains((string) json_encode($entry->properties), $oldEmail)) {
                        $entry->properties = null;
                    }
                    $entry->save();
                });

            $user->forceFill([
                'name' => $placeholder,
                'email' => "erased-{$user->id}-".Str::lower(Str::random(8)).'@erased.invalid',
                'password' => Hash::make(Str::random(64)),
                'remember_token' => null,
                'email_verified_at' => null,
            ])->save();
        });
    }
}
