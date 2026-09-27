<?php

namespace Elibrary\Lms\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\User;
use Elibrary\Lms\Models\CourseDiscussion;
use Elibrary\Lms\Support\Teaching;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Questions across the courses someone teaches, oldest unanswered first. */
class DiscussionController extends Controller
{
    public function index(Request $request): View
    {
        $waiting = $request->query('show') !== 'all';

        return view('lms::manage.discussions.index', [
            'discussions' => self::visibleTo($request->user())
                ->with(['course', 'author', 'lesson'])
                ->when($waiting, fn ($query) => $query->whereNull('staff_replied_at')->where('is_locked', false)->oldest())
                ->when(! $waiting, fn ($query) => $query->latest('last_activity_at'))
                ->paginate(25)
                ->withQueryString(),
            'waiting' => $waiting,
            'waitingCount' => self::waitingFor($request->user()),
        ]);
    }

    /** Threads in the courses this person teaches (owners: every course). */
    public static function visibleTo(User $user): Builder
    {
        return CourseDiscussion::query()
            ->when(! $user->isOwner(), fn ($query) => $query->whereIn('course_id', Teaching::courseIds($user)));
    }

    /** Open questions no instructor or owner has replied to yet. */
    public static function waitingFor(User $user): int
    {
        return self::visibleTo($user)->whereNull('staff_replied_at')->where('is_locked', false)->count();
    }
}
