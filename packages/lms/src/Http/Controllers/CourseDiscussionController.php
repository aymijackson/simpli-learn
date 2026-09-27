<?php

namespace Elibrary\Lms\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\DiscussionActivity;
use App\Support\SafeNotifier;
use App\Support\Tenancy\Tenancy;
use Elibrary\Lms\Models\Course;
use Elibrary\Lms\Models\CourseDiscussion;
use Elibrary\Lms\Models\CourseDiscussionReply;
use Elibrary\Lms\Support\Teaching;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * A course's discussion area. Enrolled learners, the course's instructors and
 * workspace owners take part; instructors and owners also moderate (pin, lock,
 * delete) and their replies are marked. Posts are plain text.
 */
class CourseDiscussionController extends Controller
{
    public function index(Request $request, string $tenant, Course $course): View
    {
        $this->authorizeParticipant($request, $course);
        $filter = $request->string('filter')->value();
        $lessonId = $request->integer('lesson') ?: null;

        $discussions = $course->discussions()
            ->with(['author', 'lesson'])
            ->when($lessonId, fn ($query) => $query->where('lesson_id', $lessonId))
            ->when($filter === 'unanswered', fn ($query) => $query->whereNull('answer_reply_id'))
            ->when($filter === 'mine', fn ($query) => $query->where('user_id', $request->user()->id))
            ->orderByDesc('is_pinned')
            ->orderByDesc('last_activity_at')
            ->paginate(20)
            ->withQueryString();

        return view('lms::discussions.index', [
            'course' => $course,
            'discussions' => $discussions,
            'filter' => in_array($filter, ['unanswered', 'mine'], true) ? $filter : 'all',
            'lesson' => $lessonId ? $course->lessons->firstWhere('id', $lessonId) : null,
            'lessons' => $course->lessons,
        ]);
    }

    public function store(Request $request, string $tenant, Course $course): RedirectResponse
    {
        $this->authorizeParticipant($request, $course);

        $validated = $request->validate([
            'title' => ['required', 'string', 'min:5', 'max:200'],
            'body' => ['required', 'string', 'min:10', 'max:5000'],
            'lesson_id' => ['nullable', 'integer', Rule::in($course->lessons->pluck('id')->all())],
        ]);

        $discussion = CourseDiscussion::create($validated + [
            'course_id' => $course->id,
            'user_id' => $request->user()->id,
            'last_activity_at' => now(),
        ]);

        // Instructors hear about new questions (owners when nobody teaches it).
        $staff = $course->instructors()->where('users.id', '!=', $request->user()->id)->get();
        if ($staff->isEmpty()) {
            $staff = $this->owners()->where('id', '!=', $request->user()->id);
        }
        SafeNotifier::send($staff->values(), new DiscussionActivity($discussion, 'new', $request->user()->name, app(Tenancy::class)->current()));

        return redirect()->route('lms.discussions.show', [$course, $discussion])->with('status', __('Your question is posted.'));
    }

    public function show(Request $request, string $tenant, Course $course, CourseDiscussion $discussion): View
    {
        $this->authorizeParticipant($request, $course);
        abort_unless($discussion->course_id === $course->id, 404);

        return view('lms::discussions.show', [
            'course' => $course,
            'discussion' => $discussion->load(['author', 'lesson', 'replies.author']),
            'isStaff' => Teaching::canManage($request->user(), $course),
        ]);
    }

    public function reply(Request $request, string $tenant, Course $course, CourseDiscussion $discussion): RedirectResponse
    {
        $this->authorizeParticipant($request, $course);
        abort_unless($discussion->course_id === $course->id, 404);
        $isStaff = Teaching::canManage($request->user(), $course);
        abort_if($discussion->is_locked && ! $isStaff, 403, 'This discussion is closed.');

        $validated = $request->validate(['body' => ['required', 'string', 'min:2', 'max:5000']]);

        DB::transaction(function () use ($discussion, $validated, $request, $isStaff) {
            $discussion->replies()->create([
                'body' => $validated['body'],
                'user_id' => $request->user()->id,
                'is_staff' => $isStaff,
            ]);
            $discussion->forceFill([
                'replies_count' => $discussion->replies()->count(),
                'last_activity_at' => now(),
                'staff_replied_at' => $discussion->staff_replied_at ?? ($isStaff ? now() : null),
            ])->save();
        });

        // Everyone else in the thread (and the course's instructors) hears about it.
        $people = User::whereIn('id', $discussion->replies()->pluck('user_id')->push($discussion->user_id)->filter()->unique())
            ->get()
            ->merge($course->instructors)
            ->unique('id')
            ->reject(fn (User $person) => $person->id === $request->user()->id)
            ->values();
        SafeNotifier::send($people, new DiscussionActivity($discussion, 'reply', $request->user()->name, app(Tenancy::class)->current()));

        return redirect()->to(route('lms.discussions.show', [$course, $discussion]).'#latest')->with('status', __('Reply posted.'));
    }

    /** The asker, or staff, marks the reply that answered the question. */
    public function answer(Request $request, string $tenant, Course $course, CourseDiscussion $discussion, CourseDiscussionReply $reply): RedirectResponse
    {
        $this->authorizeParticipant($request, $course);
        abort_unless($discussion->course_id === $course->id && $reply->course_discussion_id === $discussion->id, 404);
        abort_unless($discussion->user_id === $request->user()->id || Teaching::canManage($request->user(), $course), 403);

        $discussion->forceFill(['answer_reply_id' => $discussion->answer_reply_id === $reply->id ? null : $reply->id])->save();

        return redirect()->route('lms.discussions.show', [$course, $discussion])
            ->with('status', $discussion->answer_reply_id ? __('Marked as the answer.') : __('Answer unmarked.'));
    }

    public function moderate(Request $request, string $tenant, Course $course, CourseDiscussion $discussion): RedirectResponse
    {
        abort_unless($discussion->course_id === $course->id, 404);
        abort_unless(Teaching::canManage($request->user(), $course), 403);

        $action = $request->validate(['action' => ['required', Rule::in(['pin', 'unpin', 'lock', 'unlock'])]])['action'];
        $discussion->forceFill(match ($action) {
            'pin' => ['is_pinned' => true],
            'unpin' => ['is_pinned' => false],
            'lock' => ['is_locked' => true],
            'unlock' => ['is_locked' => false],
        })->save();

        return back()->with('status', match ($action) {
            'pin' => __('Discussion pinned to the top.'),
            'unpin' => __('Discussion unpinned.'),
            'lock' => __('Discussion closed to new replies.'),
            'unlock' => __('Discussion reopened.'),
        });
    }

    public function destroy(Request $request, string $tenant, Course $course, CourseDiscussion $discussion): RedirectResponse
    {
        abort_unless($discussion->course_id === $course->id, 404);
        $isStaff = Teaching::canManage($request->user(), $course);
        // Authors may withdraw a question nobody has answered yet.
        abort_unless($isStaff || ($discussion->user_id === $request->user()->id && $discussion->replies_count === 0), 403);

        $discussion->delete();

        return redirect()->route('lms.discussions.index', $course)->with('status', __('Discussion deleted.'));
    }

    public function destroyReply(Request $request, string $tenant, Course $course, CourseDiscussion $discussion, CourseDiscussionReply $reply): RedirectResponse
    {
        abort_unless($discussion->course_id === $course->id && $reply->course_discussion_id === $discussion->id, 404);
        abort_unless($reply->user_id === $request->user()->id || Teaching::canManage($request->user(), $course), 403);

        DB::transaction(function () use ($discussion, $reply) {
            $reply->delete();
            $discussion->forceFill([
                'replies_count' => $discussion->replies()->count(),
                'answer_reply_id' => $discussion->answer_reply_id === $reply->id ? null : $discussion->answer_reply_id,
            ])->save();
        });

        return redirect()->route('lms.discussions.show', [$course, $discussion])->with('status', __('Reply deleted.'));
    }

    private function authorizeParticipant(Request $request, Course $course): void
    {
        $user = $request->user();
        abort_unless($course->isEnrolled($user) || Teaching::canManage($user, $course), 403, 'Enrol in this course to join its discussions.');
    }

    private function owners()
    {
        return app(Tenancy::class)->current()->users()->active()->where('role', 'owner')->get();
    }
}
