<?php

namespace App\Console\Commands;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Notifications\CourseAssignmentReminder;
use App\Support\SafeNotifier;
use App\Support\Tenancy\Tenancy;
use Elibrary\Lms\Models\CourseAssignment;
use Elibrary\Lms\Reports\CourseProgressReport;
use Illuminate\Console\Command;

/**
 * Emails people whose assigned courses are nearly due or overdue.
 *
 *   - due within 3 days: one reminder
 *   - overdue: a reminder each week, for up to 30 days
 *   - finished, deactivated, or no due date: never
 *
 * Scheduled daily (routes/console.php); safe to run by hand.
 */
class SendCourseReminders extends Command
{
    protected $signature = 'courses:send-reminders {--dry-run : List who would be reminded without sending}';

    protected $description = 'Email reminders for assigned courses that are nearly due or overdue';

    public function handle(Tenancy $tenancy): int
    {
        $tenantIds = CourseAssignment::withoutGlobalScopes()->whereNotNull('due_at')->distinct()->pluck('tenant_id');
        $sent = 0;

        foreach (Tenant::whereIn('id', $tenantIds)->where('status', TenantStatus::Active)->get() as $tenant) {
            $tenancy->set($tenant);

            try {
                $sent += $this->remindWorkspace($tenant);
            } finally {
                $tenancy->forget();
            }
        }

        $this->info(($this->option('dry-run') ? 'Would send ' : 'Sent ').$sent.' '.str('reminder')->plural($sent).'.');

        return self::SUCCESS;
    }

    private function remindWorkspace(Tenant $tenant): int
    {
        $sent = 0;
        $due = CourseAssignment::query()
            ->whereNotNull('due_at')
            ->where('due_at', '<=', now()->addDays(3))
            ->where('due_at', '>=', now()->subDays(30))
            ->with(['course', 'user'])
            ->get()
            ->filter(fn (CourseAssignment $assignment) => $assignment->course && $assignment->user && ! $assignment->user->isDeactivated())
            ->filter(fn (CourseAssignment $assignment) => $this->isTimeToRemind($assignment));

        foreach ($due->groupBy('course_id') as $assignments) {
            $course = $assignments->first()->course;
            $report = CourseProgressReport::build($course, $assignments->pluck('user'))->keyBy(fn ($row) => $row['user']->id);

            foreach ($assignments as $assignment) {
                $row = $report->get($assignment->user_id);
                if ($row['status'] === CourseProgressReport::COMPLETED) {
                    continue;
                }

                $this->line("  {$tenant->name}: {$assignment->user->email} — {$course->title} (due {$assignment->due_at->toDateString()}, {$row['progress']}%)");

                if (! $this->option('dry-run')) {
                    if (SafeNotifier::send($assignment->user, new CourseAssignmentReminder($assignment, $tenant, $row['progress']))) {
                        $assignment->forceFill(['reminded_at' => now()])->save();
                    }
                }
                $sent++;
            }
        }

        return $sent;
    }

    private function isTimeToRemind(CourseAssignment $assignment): bool
    {
        if ($assignment->reminded_at === null) {
            return true;
        }

        // Overdue: at most weekly. Before the due date: the one reminder is enough.
        return $assignment->isOverdue()
            && $assignment->reminded_at->lt($assignment->due_at->copy()->max(now()->subDays(7)));
    }
}
