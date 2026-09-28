<?php

namespace App\Support;

use App\Models\SupportConversation;
use App\Models\SupportMessage;
use App\Models\User;
use App\Notifications\SupportMessageReceived;
use App\Notifications\SupportReplyReceived;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The support chat's rules in one place: starting/reopening a
 * conversation, posting, read markers and who gets told.
 */
class SupportChat
{
    /** Staff are emailed about new messages at most this often per conversation. */
    public const EMAIL_EVERY_MINUTES = 30;

    public const MAX_LENGTH = 2000;

    /** The person's own conversation on their desk, if they've started one. */
    public static function conversationOf(User $user): ?SupportConversation
    {
        $desk = SupportConversation::deskFor($user);

        return $desk ? SupportConversation::withoutGlobalScopes()->where('user_id', $user->id)->where('desk', $desk)->first() : null;
    }

    /** A message from the person who asked; starts or reopens their conversation. */
    public static function ask(User $user, string $body): SupportMessage
    {
        $desk = SupportConversation::deskFor($user);
        abort_unless($desk, 403);

        [$conversation, $message] = DB::transaction(function () use ($user, $desk, $body) {
            $conversation = SupportConversation::withoutGlobalScopes()->firstOrCreate(
                ['user_id' => $user->id, 'desk' => $desk],
                ['tenant_id' => $user->tenant_id, 'status' => 'open'],
            );

            $message = self::add($conversation, $user, $body, fromStaff: false);
            $conversation->forceFill(['status' => 'open', 'requester_read_id' => $message->id])->save();

            return [$conversation, $message];
        });

        self::tellStaff($conversation, $message);

        return $message;
    }

    /** A reply from staff (a workspace owner or a platform admin). */
    public static function reply(SupportConversation $conversation, User $staff, string $body): SupportMessage
    {
        abort_unless($conversation->isStaff($staff), 403);

        $message = DB::transaction(function () use ($conversation, $staff, $body) {
            $message = self::add($conversation, $staff, $body, fromStaff: true);
            $conversation->forceFill(['status' => 'open', 'staff_read_id' => $message->id])->save();

            return $message;
        });

        $requester = $conversation->user;
        if ($requester && ! $requester->isDeactivated()) {
            SafeNotifier::send($requester, new SupportReplyReceived($conversation, $message));
        }

        return $message;
    }

    /** Everything up to the latest message is now seen by this side. */
    public static function markReadBy(SupportConversation $conversation, User $user): void
    {
        $column = $conversation->user_id === $user->id ? 'requester_read_id' : 'staff_read_id';
        $latest = (int) SupportMessage::withoutGlobalScopes()->where('conversation_id', $conversation->id)->max('id');
        $conversation->forceFill([$column => $latest])->saveQuietly();
    }

    /** Workspace owners (for the workspace desk) or platform admins. */
    public static function staffFor(SupportConversation $conversation): Collection
    {
        $query = User::withoutGlobalScopes()->whereNull('deactivated_at');

        return $conversation->desk === SupportConversation::PLATFORM
            ? $query->whereNull('tenant_id')->get()
            : $query->where('tenant_id', $conversation->tenant_id)->where('role', 'owner')->whereKeyNot($conversation->user_id)->get();
    }

    private static function add(SupportConversation $conversation, User $sender, string $body, bool $fromStaff): SupportMessage
    {
        $message = SupportMessage::withoutGlobalScopes()->create([
            'conversation_id' => $conversation->id,
            'tenant_id' => $conversation->tenant_id,
            'user_id' => $sender->id,
            'from_staff' => $fromStaff,
            'body' => self::clean($body),
        ]);
        $conversation->forceFill(['last_message_at' => $message->created_at])->save();

        return $message;
    }

    /** Plain text only: trimmed, no control characters, capped. */
    private static function clean(string $body): string
    {
        $body = preg_replace('/[^\P{C}\n\t]/u', '', str_replace("\r\n", "\n", $body)) ?? '';

        return mb_substr(trim($body), 0, self::MAX_LENGTH);
    }

    /** In-app for owners every time; an email at most every 30 minutes per conversation. */
    private static function tellStaff(SupportConversation $conversation, SupportMessage $message): void
    {
        $email = ! $conversation->staff_emailed_at || $conversation->staff_emailed_at->lt(now()->subMinutes(self::EMAIL_EVERY_MINUTES));
        $staff = self::staffFor($conversation);

        if ($staff->isNotEmpty() && SafeNotifier::send($staff, new SupportMessageReceived($conversation, $message, $email)) && $email) {
            $conversation->forceFill(['staff_emailed_at' => now()])->saveQuietly();
        }
    }
}
