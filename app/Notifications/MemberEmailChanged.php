<?php

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to a member's old address when a workspace owner changes their login
 * email, so the person can raise the alarm if they didn't expect it.
 */
class MemberEmailChanged extends Notification
{
    public function __construct(
        public Tenant $workspace,
        public string $oldEmail,
        public string $newEmail,
        public string $changedBy,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Your login email for :workspace has changed', ['workspace' => $this->workspace->name]))
            ->line(__(':name changed the email you use to log in to :workspace from :old to :new.', [
                'name' => $this->changedBy,
                'workspace' => $this->workspace->name,
                'old' => $this->oldEmail,
                'new' => $this->newEmail,
            ]))
            ->line(__('From now on, log in with the new address. This one no longer works for :workspace.', ['workspace' => $this->workspace->name]))
            ->line(__("If you didn't expect this, contact your workspace administrator straight away."));
    }
}
