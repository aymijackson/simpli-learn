<?php

namespace App\Notifications;

use App\Models\Tenant;
use App\Notifications\Concerns\ShowsInApp;
use Elibrary\Library\Models\LibraryHold;
use Elibrary\Library\Models\LibraryResource;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** A waitlisted copy is now reserved for this person, for a few days. */
class LibraryCopyReady extends Notification
{
    use ShowsInApp;

    public function __construct(public LibraryResource $resource, public LibraryHold $hold, public Tenant $tenant)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    protected function inApp(object $notifiable): array
    {
        return [
            'title' => __('A copy is ready for you'),
            'body' => __('“:title” is reserved for you until :date. Claim it before then.', ['title' => $this->resource->title, 'date' => $this->hold->expires_at->translatedFormat('j M')]),
            'url' => $this->url(),
            'icon' => 'book-open',
            'tone' => 'emerald',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('A copy of “:title” is ready for you', ['title' => $this->resource->title]))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name]))
            ->line(__('Good news — a copy of **:title** is now free, and it is reserved for you.', ['title' => $this->resource->title]))
            ->line(__('Claim it by **:date**. After that it goes to the next person on the waitlist.', ['date' => $this->hold->expires_at->translatedFormat('l, j F Y')]))
            ->action(__('Claim your copy'), $this->url());
    }

    private function url(): string
    {
        return route('library.resources.show', ['tenant' => $this->tenant->slug, 'resource' => $this->resource->slug]);
    }
}
