<?php

namespace App\Http\Controllers;

use App\Models\SupportConversation;
use App\Support\SupportChat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The chat bubble's JSON endpoints for the person asking for help:
 * learners write to their workspace's owners, owners to the platform.
 */
class SupportChatController extends Controller
{
    /** Messages after ?after=, plus the unread count. ?read=1 marks them read (panel open). */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless(SupportConversation::deskFor($user), 403);

        $conversation = SupportChat::conversationOf($user);
        if (! $conversation) {
            return response()->json(['messages' => [], 'unread' => 0, 'status' => null]);
        }

        $messages = $conversation->messages()->with('sender', 'conversation')
            ->where('id', '>', (int) $request->query('after', 0))
            ->limit(200)->get();

        if ($request->boolean('read')) {
            SupportChat::markReadBy($conversation, $user);
        }

        return response()->json([
            'messages' => $messages->map->toChat($user)->values(),
            'unread' => $request->boolean('read') ? 0 : $conversation->unreadForRequester(),
            'status' => $conversation->status,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(['body' => ['required', 'string', 'max:'.SupportChat::MAX_LENGTH]]);

        $message = SupportChat::ask($request->user(), $validated['body']);

        return response()->json(['message' => $message->load('sender', 'conversation')->toChat($request->user())], 201);
    }
}
