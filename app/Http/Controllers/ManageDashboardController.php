<?php

namespace App\Http\Controllers;

use App\Enums\Module;
use App\Models\User;
use App\Support\Tenancy\Tenancy;
use Elibrary\Cbt\Models\Certificate;
use Elibrary\Cbt\Models\CertificatePayment;
use Elibrary\Cbt\Models\Exam;
use Elibrary\Cbt\Models\ExamAttempt;
use Elibrary\Library\Models\LibraryCheckout;
use Elibrary\Library\Models\LibraryResource;
use Elibrary\Library\Models\LibraryResourcePurchase;
use Elibrary\Lms\Models\Course;
use Elibrary\Lms\Models\CoursePurchase;
use Elibrary\Lms\Models\Enrollment;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * The owner's back-office home: headline numbers for each enabled module,
 * bank transfers waiting for confirmation, and recent learner activity.
 */
class ManageDashboardController extends Controller
{
    public function __invoke(Tenancy $tenancy): View
    {
        $tenant = $tenancy->current();
        $enabled = $tenant->tenantModules()->where('is_enabled', true)->get()->map(fn ($tenantModule) => $tenantModule->module);
        $since = now()->subDays(30);

        $stats = [
            'members' => User::where('tenant_id', $tenant->id)->count(),
            'newMembers' => User::where('tenant_id', $tenant->id)->where('created_at', '>=', $since)->count(),
        ];
        $activity = collect();

        if ($enabled->contains(Module::Lms)) {
            $stats['lms'] = [
                'courses' => Course::count(),
                'published' => Course::where('is_published', true)->count(),
                'enrollments' => Enrollment::count(),
                'recentEnrollments' => Enrollment::where('created_at', '>=', $since)->count(),
                'sales' => CoursePurchase::where('status', 'paid')->count(),
            ];

            $activity = $activity->merge(
                Enrollment::with(['user', 'course'])->latest()->take(8)->get()
                    ->filter(fn ($enrollment) => $enrollment->user && $enrollment->course)
                    ->map(fn ($enrollment) => [
                        'icon' => 'academic-cap',
                        'tone' => 'indigo',
                        'text' => "{$enrollment->user->name} enrolled in {$enrollment->course->title}",
                        'at' => $enrollment->created_at,
                    ])
            );
        }

        if ($enabled->contains(Module::Cbt)) {
            $submitted = ExamAttempt::whereNotNull('submitted_at');

            $stats['cbt'] = [
                'exams' => Exam::count(),
                'published' => Exam::where('is_published', true)->count(),
                'attempts' => (clone $submitted)->count(),
                'recentAttempts' => (clone $submitted)->where('submitted_at', '>=', $since)->count(),
                'averageScore' => (int) round((float) (clone $submitted)->avg('score')),
                'certificates' => Certificate::count(),
                'toMark' => ExamAttempt::where('needs_marking', true)->whereNotNull('submitted_at')->count(),
            ];

            $activity = $activity->merge(
                ExamAttempt::whereNotNull('submitted_at')->with(['user', 'exam'])->latest('submitted_at')->take(8)->get()
                    ->filter(fn ($attempt) => $attempt->user && $attempt->exam)
                    ->map(fn ($attempt) => [
                        'icon' => 'clipboard-check',
                        'tone' => $attempt->isAwaitingMarking() ? 'amber' : ($attempt->passed() ? 'emerald' : 'rose'),
                        'text' => $attempt->isAwaitingMarking()
                            ? "{$attempt->user->name} submitted {$attempt->exam->title} — awaiting marking"
                            : "{$attempt->user->name} scored {$attempt->score}% on {$attempt->exam->title}",
                        'at' => $attempt->submitted_at,
                    ])
            );
        }

        if ($enabled->contains(Module::Library)) {
            $open = LibraryCheckout::whereNull('returned_at');

            $stats['library'] = [
                'resources' => LibraryResource::count(),
                'published' => LibraryResource::where('is_published', true)->count(),
                'onLoan' => (clone $open)->count(),
                'overdue' => (clone $open)->where('due_at', '<', now())->count(),
            ];

            $activity = $activity->merge(
                LibraryCheckout::with(['user', 'resource'])->latest('checked_out_at')->take(8)->get()
                    ->filter(fn ($checkout) => $checkout->user && $checkout->resource)
                    ->map(fn ($checkout) => [
                        'icon' => 'book-open',
                        'tone' => 'amber',
                        'text' => "{$checkout->user->name} borrowed {$checkout->resource->title}",
                        'at' => $checkout->checked_out_at,
                    ])
            );
        }

        return view('manage.dashboard', [
            'tenant' => $tenant,
            'enabled' => $enabled,
            'stats' => $stats,
            'reviews' => self::pendingPaymentReviews($enabled),
            'activity' => $activity->filter(fn ($item) => $item['at'])->sortByDesc('at')->take(10)->values(),
        ]);
    }

    /**
     * Bank-transfer payments learners have started that an owner still has
     * to confirm or reject, per enabled module. Also feeds the sidebar badges.
     *
     * @param  Collection<int, Module>  $enabled
     * @return array<string, int>
     */
    public static function pendingPaymentReviews(Collection $enabled): array
    {
        $pending = fn (string $model) => $model::where('status', 'pending')->where('gateway', 'bank_transfer')->count();

        return array_filter([
            'lms' => $enabled->contains(Module::Lms) ? $pending(CoursePurchase::class) : 0,
            'cbt' => $enabled->contains(Module::Cbt) ? $pending(CertificatePayment::class) : 0,
            'library' => $enabled->contains(Module::Library) ? $pending(LibraryResourcePurchase::class) : 0,
        ]);
    }
}
