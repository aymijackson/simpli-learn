<?php

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkspaceRejected extends Notification
{
    public function __construct(public Tenant $tenant)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Your e-Library workspace request for {$this->tenant->name}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Thank you for your interest in e-Library. We weren't able to approve the workspace **{$this->tenant->name}** at this time.")
            ->line('If you think this is a mistake or would like to share more about your organisation, simply reply to this email.');
    }
}
