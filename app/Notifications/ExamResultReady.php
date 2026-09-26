<?php

namespace App\Notifications;

use App\Models\Tenant;
use Elibrary\Cbt\Models\ExamAttempt;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExamResultReady extends Notification
{
    public function __construct(public ExamAttempt $attempt, public Tenant $tenant)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
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
