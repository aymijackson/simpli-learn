<?php

namespace App\Notifications;

use App\Models\Tenant;
use App\Notifications\Concerns\ShowsInApp;
use Elibrary\Library\Models\LibraryCheckout;
use Illuminate\Notifications\Notification;

/** A loan reached its due date and was returned automatically. In-app only. */
class LibraryLoanEnded extends Notification
{
    use ShowsInApp;

    public function __construct(public LibraryCheckout $checkout, public Tenant $tenant)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    protected function inApp(object $notifiable): array
    {
        return [
            'title' => __('Loan ended'),
            'body' => __('Your loan of “:title” has ended and it was returned to the library.', ['title' => $this->checkout->resource->title]),
            'url' => route('library.resources.show', ['tenant' => $this->tenant->slug, 'resource' => $this->checkout->resource->slug]),
            'icon' => 'book-open',
            'tone' => 'indigo',
        ];
    }
}
