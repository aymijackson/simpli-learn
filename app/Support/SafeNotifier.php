<?php

namespace App\Support;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Throwable;

/**
 * Sends notifications without letting a mail problem break the action that
 * triggered them. Mail is sent immediately (cPanel hosting has no queue
 * worker), so an unreachable SMTP server would otherwise surface as a 500
 * on, say, the "Approve workspace" button. Failures are reported to the log.
 */
class SafeNotifier
{
    public static function send(mixed $notifiables, Notification $notification): bool
    {
        if (collect($notifiables)->isEmpty()) {
            return true;
        }

        try {
            NotificationFacade::send($notifiables, $notification);

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }
}
