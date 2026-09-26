<?php

namespace App\Support;

use App\Enums\Badge;
use App\Models\LearningDay;
use App\Models\User;
use App\Models\UserBadge;
use App\Notifications\BadgeEarned;
use Elibrary\Cbt\Models\ExamAttempt;
use Elibrary\Lms\Models\Enrollment;
use Elibrary\Lms\Models\LessonProgress;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Learning streaks and badges. A day counts towards a streak when the person
 * opens or completes a lesson or submits an exam. Badges are checked at those
 * moments and awarded once each, with an in-app notification.
 */
class Achievements
{
    /** Note that the person learned today; returns true the first time each day. */
    public static function recordActivity(User $user): bool
    {
        $inserted = LearningDay::withoutGlobalScopes()->insertOrIgnore([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'day' => today()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]) > 0;

        if ($inserted) {
            self::awardStreakBadges($user);
        }

        return $inserted;
    }

    /**
     * @return array{current: int, best: int, today: bool, week: array<string, bool>}
     */
    public static function streak(User $user): array
    {
        $days = LearningDay::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->orderByDesc('day')
            ->limit(400)
            ->pluck('day')
            ->map(fn ($day) => Carbon::parse($day)->toDateString());
        $set = $days->flip();

        // The streak survives until the end of today even if today is empty.
        $cursor = $set->has(today()->toDateString()) ? today() : today()->subDay();
        $current = 0;
        while ($set->has($cursor->toDateString())) {
            $current++;
            $cursor = $cursor->subDay();
        }

        $best = 0;
        $run = 0;
        $previous = null;
        foreach ($days->reverse() as $day) {
            $run = $previous && Carbon::parse($previous)->addDay()->toDateString() === $day ? $run + 1 : 1;
            $best = max($best, $run);
            $previous = $day;
        }

        $week = collect(range(6, 0))->mapWithKeys(fn ($ago) => [
            today()->subDays($ago)->toDateString() => $set->has(today()->subDays($ago)->toDateString()),
        ])->all();

        return ['current' => $current, 'best' => max($best, $current), 'today' => $set->has(today()->toDateString()), 'week' => $week];
    }

    /** @return Collection<int, UserBadge> Earned badges, in the order they're listed. */
    public static function badgesFor(User $user): Collection
    {
        $order = array_flip(array_column(Badge::cases(), 'value'));

        return UserBadge::withoutGlobalScopes()->where('user_id', $user->id)->get()
            ->sortBy(fn (UserBadge $badge) => $order[$badge->badge->value])
            ->values();
    }

    /** Check every learning badge and award any newly earned. */
    public static function evaluate(User $user): void
    {
        $lessons = LessonProgress::withoutGlobalScopes()->where('user_id', $user->id)->whereNotNull('completed_at')->count();
        $courses = Enrollment::withoutGlobalScopes()->where('user_id', $user->id)->with(['course' => fn ($query) => $query->withoutGlobalScopes()])->get()
            ->filter(fn ($enrollment) => $enrollment->course?->isPassedBy($user))
            ->count();
        $passed = ExamAttempt::withoutGlobalScopes()->where('user_id', $user->id)->whereNotNull('submitted_at')->whereNotNull('score')
            ->whereHas('exam', fn ($query) => $query->withoutGlobalScopes()->whereColumn('exam_attempts.score', '>=', 'exams.pass_percentage'))
            ->exists();
        $perfect = ExamAttempt::withoutGlobalScopes()->where('user_id', $user->id)->whereNotNull('submitted_at')->where('score', '>=', 100)->exists();

        self::award($user, array_keys(array_filter([
            Badge::FirstLesson->value => $lessons >= 1,
            Badge::FirstCourse->value => $courses >= 1,
            Badge::FiveCourses->value => $courses >= 5,
            Badge::FirstPass->value => $passed,
            Badge::PerfectScore->value => $perfect,
        ])));
        self::awardStreakBadges($user);
    }

    private static function awardStreakBadges(User $user): void
    {
        $best = self::streak($user)['best'];

        self::award($user, array_keys(array_filter([
            Badge::Streak7->value => $best >= 7,
            Badge::Streak30->value => $best >= 30,
        ])));
    }

    /** @param  array<int, string>  $badges */
    private static function award(User $user, array $badges): void
    {
        foreach ($badges as $value) {
            $created = UserBadge::withoutGlobalScopes()->insertOrIgnore([
                'tenant_id' => $user->tenant_id,
                'user_id' => $user->id,
                'badge' => $value,
                'earned_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]) > 0;

            if ($created && $user->tenant) {
                SafeNotifier::send($user, new BadgeEarned(Badge::from($value), $user->tenant));
            }
        }
    }
}
