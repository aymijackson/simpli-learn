<?php

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkspaceApproved extends Notification
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
            ->subject("{$this->tenant->name} is live on e-Library")
            ->greeting("Good news, {$notifiable->name}!")
            ->line("Your workspace **{$this->tenant->name}** has been approved and is ready to use.")
            ->action('Log in to your workspace', route('tenant.login', $this->tenant->slug))
            ->line('Next steps: add your courses, exams or library resources, then add your team under Manage → Team & learners.')
            ->line('Your workspace address is '.route('tenant.home', $this->tenant->slug).' — share it with your learners.');
    }
}
