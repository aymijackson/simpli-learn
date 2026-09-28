<?php

namespace App\Notifications;

use App\Models\ContactMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/** Emails platform admins a new contact-form message; "Reply" goes to the sender. */
class ContactMessageReceived extends Notification
{
    public function __construct(public ContactMessage $contact)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Contact form: '.Str::limit($this->contact->subject ?: 'New message', 80))
            ->replyTo($this->contact->email, $this->contact->name)
            ->greeting('New message from the website')
            ->line("From: {$this->contact->name} <{$this->contact->email}>")
            ->line(Str::limit($this->contact->message, 1500))
            ->action('Open in the admin area', route('admin.contact-messages.show', $this->contact))
            ->line('Reply to this email to answer them directly.');
    }
}
