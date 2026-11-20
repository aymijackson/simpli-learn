<?php

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/**
 * The "forgot your workspace?" email: links to the login page of every
 * active workspace this email address has an account in. Sent only to the
 * address itself, so the list is never shown to anyone who doesn't own it.
 */
class YourWorkspaces extends Notification
{
    /** @param  Collection<int, Tenant>  $workspaces */
    public function __construct(public Collection $workspaces)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject(__('Your e-Library workspaces'))
            ->line(trans_choice('This email address can log in to this workspace:|This email address can log in to these workspaces:', $this->workspaces->count()));

        foreach ($this->workspaces as $workspace) {
            $message->line('['.$workspace->name.']('.route('tenant.login', $workspace->slug).')');
        }

        if ($this->workspaces->count() === 1) {
            $message->action(__('Log in to :workspace', ['workspace' => $this->workspaces->first()->name]), route('tenant.login', $this->workspaces->first()->slug));
        }

        return $message
            ->line(__('Tip: bookmark your workspace\'s login page, or log in from the main login page with your email and password and we\'ll take you there.'))
            ->line(__("If you didn't ask for this email, you can ignore it."));
    }
}
