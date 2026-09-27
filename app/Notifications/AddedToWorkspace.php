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
            ->subject(__("You've been invited to :workspace", ['workspace' => $this->tenant->name]))
            ->greeting(__('Welcome, :name!', ['name' => $notifiable->name]))
            ->line(__(':by has added you to **:workspace** on e-Library, where you can take courses, sit exams and use the digital library.', ['by' => $this->addedBy, 'workspace' => $this->tenant->name]));

        if ($this->setPasswordUrl) {
            return $message
                ->line(__('To get started, choose a password for your account.'))
                ->action(__('Set your password'), $this->setPasswordUrl)
                ->line(__('This link works for 7 days. After that, use **Forgot password?** on the login page, or ask :by to send a new invitation.', ['by' => $this->addedBy]))
                ->line(__('Your login email is :email.', ['email' => $notifiable->email]));
        }

        return $message
            ->line(__('Log in with this email address (:email).', ['email' => $notifiable->email]))
            ->action(__('Log in'), route('tenant.login', $this->tenant->slug))
            ->line(__("Don't know your password? Use **Forgot password?** on the login page to set your own."));
    }
}
