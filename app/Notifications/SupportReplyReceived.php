<?php

namespace App\Notifications;

use App\Models\SupportConversation;
use App\Models\SupportMessage;
use App\Notifications\Concerns\ShowsInApp;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/** In-app: support replied to your chat. Opening it opens the chat bubble. */
class SupportReplyReceived extends Notification
{
    use ShowsInApp;

    public function __construct(public SupportConversation $conversation, public SupportMessage $message)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    protected function inApp(object $notifiable): array
    {
        $home = $this->conversation->desk === SupportConversation::PLATFORM
            ? route('tenant.manage.dashboard', ['tenant' => $this->conversation->tenant->slug])
            : route('tenant.home', ['tenant' => $this->conversation->tenant->slug]);

        return [
            'title' => __('Support replied'),
            'body' => Str::limit($this->message->body, 120),
            'url' => $home.'#support',
            'icon' => 'chat',
            'tone' => 'emerald',
        ];
    }
}
