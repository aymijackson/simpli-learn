<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\SupportConversation;
use App\Support\SupportChat;
use App\Support\Tenancy\Tenancy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The support inbox for whoever answers: workspace owners (their
 * learners' chats, under /t/{tenant}/manage/support) and platform admins
 * (owners' chats from every workspace, under /admin/support).
 */
class SupportInboxController extends Controller
{
    public function index(Request $request): View
    {
        return $this->page($request, null);
    }

    public function show(Request $request): View
    {
        return $this->page($request, $this->find($request));
    }

    /** New messages for the open conversation (the page polls this). */
    public function messages(Request $request): JsonResponse
    {
        $conversation = $this->find($request);
        $messages = $conversation->messages()->with('sender', 'conversation')
            ->where('id', '>', (int) $request->query('after', 0))->limit(200)->get();

        if ($messages->isNotEmpty()) {
            SupportChat::markReadBy($conversation, $request->user());
        }

        return response()->json([
            'messages' => $messages->map->toChat($request->user())->values(),
            'status' => $conversation->status,
            'waiting' => $this->conversations($request)->awaitingStaff()->count(),
        ]);
    }

    public function reply(Request $request): JsonResponse|RedirectResponse
    {
        $conversation = $this->find($request);
        $validated = $request->validate(['body' => ['required', 'string', 'max:'.SupportChat::MAX_LENGTH]]);

        $message = SupportChat::reply($conversation, $request->user(), $validated['body']);

        return $request->expectsJson()
            ? response()->json(['message' => $message->load('sender', 'conversation')->toChat($request->user())], 201)
            : back();
    }

    public function resolve(Request $request): RedirectResponse
    {
        $conversation = $this->find($request);
        $conversation->forceFill(['status' => $conversation->status === 'resolved' ? 'open' : 'resolved'])->save();
        SupportChat::markReadBy($conversation, $request->user());

        ActivityLog::record('support.'.$conversation->status, ucfirst($conversation->status).' a support chat with '.($conversation->user?->name ?? 'a former member'), tenantId: $conversation->tenant_id);

        return back()->with('status', $conversation->status === 'resolved' ? 'Marked as resolved.' : 'Reopened.');
    }

    /** Whose conversations this inbox holds. */
    private function conversations(Request $request): Builder
    {
        // Belt and braces: only platform admins (no workspace) use the platform inbox.
        abort_if($this->isPlatform($request) && $request->user()->tenant_id !== null, 403);

        $query = SupportConversation::withoutGlobalScopes()->whereNotNull('last_message_at');

        return $this->isPlatform($request)
            ? $query->where('desk', SupportConversation::PLATFORM)
            : $query->where('desk', SupportConversation::WORKSPACE)->where('tenant_id', app(Tenancy::class)->id());
    }

    private function find(Request $request): SupportConversation
    {
        $conversation = $this->conversations($request)->findOrFail((int) $request->route('conversation'));
        abort_unless($conversation->isStaff($request->user()), 403);

        return $conversation;
    }

    private function isPlatform(Request $request): bool
    {
        return $request->routeIs('admin.*');
    }

    private function page(Request $request, ?SupportConversation $open): View
    {
        $filter = $request->query('show') === 'resolved' ? 'resolved' : 'open';
        $conversations = $this->conversations($request)
            ->where('status', $filter)
            ->with('user', 'tenant')
            ->orderByDesc('last_message_at')
            ->paginate(30)->withQueryString();
        $waitingIds = $this->conversations($request)->awaitingStaff()->pluck('id');

        if ($open) {
            SupportChat::markReadBy($open, $request->user());
        }

        return view('support.inbox', [
            'platform' => $this->isPlatform($request),
            'conversations' => $conversations,
            'waitingIds' => $waitingIds,
            'filter' => $filter,
            'open' => $open,
            'messages' => $open ? $open->messages()->with('sender', 'conversation')->get()->map->toChat($request->user()) : collect(),
        ]);
    }
}
