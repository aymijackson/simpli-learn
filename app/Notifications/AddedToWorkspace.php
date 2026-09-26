<?php

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Welcome email for someone an owner has added to their workspace. It never
 * contains the password — it points the person at "Forgot password?" to set
 * one of their own if they weren't given it.
 */
class AddedToWorkspace extends Notification
{
    public function __construct(public Tenant $tenant, public string $addedBy)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("You've been added to {$this->tenant->name}")
            ->greeting("Welcome, {$notifiable->name}!")
            ->line("{$this->addedBy} has added you to **{$this->tenant->name}** on e-Library, where you can take courses, sit exams and use the digital library.")
            ->line("Log in with this email address ({$notifiable->email}).")
            ->action('Log in', route('tenant.login', $this->tenant->slug))
            ->line("Don't know your password? Use **Forgot password?** on the login page to set your own.");
    }
}
