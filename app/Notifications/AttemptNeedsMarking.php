<?php

namespace App\Notifications;

use App\Models\Tenant;
use App\Notifications\Concerns\ShowsInApp;
use Elibrary\Cbt\Models\ExamAttempt;
use Illuminate\Notifications\Notification;

/** Tells owners, in the app only, that a submitted exam has essays to mark. */
class AttemptNeedsMarking extends Notification
{
    use ShowsInApp;

    public function __construct(public ExamAttempt $attempt, public Tenant $tenant)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    protected function inApp(object $notifiable): array
    {
        return [
            'title' => 'Written answers to mark',
            'body' => "{$this->attempt->user?->name} submitted {$this->attempt->exam->title}.",
            'url' => route('cbt.manage.marking.show', ['tenant' => $this->tenant->slug, 'attempt' => $this->attempt->id]),
            'icon' => 'pencil',
            'tone' => 'amber',
        ];
    }
}
