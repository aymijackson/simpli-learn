<?php

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Welcome email for someone an owner has added to their workspace. It never
 * contains a password: invitations carry a 7-day "set your password" link;
 * people given a password by the owner are pointed at the login page.
 */
class AddedToWorkspace extends Notification
{
    public function __construct(public Tenant $tenant, public string $addedBy, public ?string $setPasswordUrl = null)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject("You've been invited to {$this->tenant->name}")
            ->greeting("Welcome, {$notifiable->name}!")
            ->line("{$this->addedBy} has added you to **{$this->tenant->name}** on e-Library, where you can take courses, sit exams and use the digital library.");

        if ($this->setPasswordUrl) {
            return $message
                ->line('To get started, choose a password for your account.')
                ->action('Set your password', $this->setPasswordUrl)
                ->line("This link works for 7 days. After that, use **Forgot password?** on the login page, or ask {$this->addedBy} to send a new invitation.")
                ->line('Your login email is '.$notifiable->email.'.');
        }

        return $message
            ->line("Log in with this email address ({$notifiable->email}).")
            ->action('Log in', route('tenant.login', $this->tenant->slug))
            ->line("Don't know your password? Use **Forgot password?** on the login page to set your own.");
    }
}
