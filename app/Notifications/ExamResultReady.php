<?php

namespace App\Notifications;

use App\Models\Tenant;
use Elibrary\Cbt\Models\ExamAttempt;
use App\Notifications\Concerns\ShowsInApp;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExamResultReady extends Notification
{
    use ShowsInApp;

    public function __construct(public ExamAttempt $attempt, public Tenant $tenant)
    {
    }

    public function via(object $notifiable): array
    {
        // In-app first, so it's recorded even if the mail server is down.
        return ['database', 'mail'];
    }

    protected function inApp(object $notifiable): array
    {
        return [
            'title' => 'Your result is ready',
            'body' => "{$this->attempt->exam->title}: {$this->attempt->score}% — ".($this->attempt->passed() ? 'passed.' : 'not passed this time.'),
            'url' => route('cbt.attempts.result', ['tenant' => $this->tenant->slug, 'attempt' => $this->attempt->id]),
            'icon' => 'clipboard-check',
            'tone' => $this->attempt->passed() ? 'emerald' : 'rose',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $exam = $this->attempt->exam;
        $message = (new MailMessage)
            ->subject("Your result is ready: {$exam->title}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your written answers on **{$exam->title}** have been marked. You scored **{$this->attempt->score}%** (pass mark {$exam->pass_percentage}%).");

        $message->line($this->attempt->passed()
            ? 'Congratulations — you passed.'
            : "You didn't reach the pass mark this time. Your result page shows the marker's feedback.");

        return $message->action('View your result', route('cbt.attempts.result', ['tenant' => $this->tenant->slug, 'attempt' => $this->attempt->id]));
    }
}
