<?php

namespace App\Notifications;

use App\Models\Tenant;
use App\Notifications\Concerns\ShowsInApp;
use Elibrary\Library\Models\LibraryCheckout;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** A borrowed resource is due back soon; access ends on the due date. */
class LibraryLoanDueSoon extends Notification
{
    use ShowsInApp;

    public function __construct(public LibraryCheckout $checkout, public Tenant $tenant)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    protected function inApp(object $notifiable): array
    {
        return [
            'title' => __('Loan ending soon'),
            'body' => __('Your loan of “:title” ends :date.', ['title' => $this->checkout->resource->title, 'date' => $this->checkout->due_at->translatedFormat('j M, H:i')]),
            'url' => $this->url(),
            'icon' => 'clock',
            'tone' => 'amber',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $title = $this->checkout->resource->title;

        return (new MailMessage)
            ->subject(__('Your loan of “:title” ends soon', ['title' => $title]))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name]))
            ->line(__('Your loan of **:title** ends on **:date**. You will lose access then, and the copy goes back to the library.', ['title' => $title, 'date' => $this->checkout->due_at->translatedFormat('l, j F Y, H:i')]))
            ->line(__('Finish what you need before then, or borrow it again afterwards if a copy is free.'))
            ->action(__('Open the resource'), $this->url());
    }

    private function url(): string
    {
        return route('library.resources.show', ['tenant' => $this->tenant->slug, 'resource' => $this->checkout->resource->slug]);
    }
}
