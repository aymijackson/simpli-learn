<?php

namespace App\Notifications;

use App\Models\SupportConversation;
use App\Models\SupportMessage;
use App\Notifications\Concerns\ShowsInApp;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Tells whoever answers a support desk about a new message: in-app for
 * workspace owners, and by email when $email is set (at most every 30
 * minutes per conversation, decided by SupportChat).
 */
class SupportMessageReceived extends Notification
{
    use ShowsInApp;

    public function __construct(public SupportConversation $conversation, public SupportMessage $message, public bool $email = true)
    {
    }

    public function via(object $notifiable): array
    {
        // Platform admins have no in-app bell; they get the email and the inbox badge.
        $channels = $notifiable->tenant_id ? ['database'] : [];

        return $this->email ? [...$channels, 'mail'] : $channels;
    }

    protected function inApp(object $notifiable): array
    {
        return [
            'title' => __('New support message'),
            'body' => __(':name: :message', ['name' => $this->senderName(), 'message' => Str::limit($this->message->body, 90)]),
            'url' => $this->url(),
            'icon' => 'chat',
            'tone' => 'indigo',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $workspace = $this->conversation->tenant?->name;

        return (new MailMessage)
            ->subject(__('New support message from :name', ['name' => $this->senderName()]))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name]))
            ->line($this->conversation->desk === SupportConversation::PLATFORM
                ? __(':name (owner of :workspace) sent a support message:', ['name' => $this->senderName(), 'workspace' => $workspace])
                : __(':name sent a support message:', ['name' => $this->senderName()]))
            ->line('> '.Str::limit($this->message->body, 500))
            ->action(__('Open the support inbox'), $this->url());
    }

    private function senderName(): string
    {
        return $this->conversation->user?->name ?? __('A learner');
    }

    private function url(): string
    {
        return $this->conversation->desk === SupportConversation::PLATFORM
            ? route('admin.support.show', $this->conversation)
            : route('tenant.manage.support.show', ['tenant' => $this->conversation->tenant->slug, 'conversation' => $this->conversation->id]);
    }
}
