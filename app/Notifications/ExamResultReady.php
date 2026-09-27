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
            'title' => __('Your result is ready'),
            'body' => __($this->attempt->passed() ? ':exam: :score% — passed.' : ':exam: :score% — not passed this time.', ['exam' => $this->attempt->exam->title, 'score' => $this->attempt->score]),
            'url' => route('cbt.attempts.result', ['tenant' => $this->tenant->slug, 'attempt' => $this->attempt->id]),
            'icon' => 'clipboard-check',
            'tone' => $this->attempt->passed() ? 'emerald' : 'rose',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $exam = $this->attempt->exam;
        $message = (new MailMessage)
            ->subject(__('Your result is ready: :exam', ['exam' => $exam->title]))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name]))
            ->line(__('Your written answers on **:exam** have been marked. You scored **:score%** (pass mark :pass%).', ['exam' => $exam->title, 'score' => $this->attempt->score, 'pass' => $exam->pass_percentage]));

        $message->line($this->attempt->passed()
            ? __('Congratulations — you passed.')
            : __("You didn't reach the pass mark this time. Your result page shows the marker's feedback."));

        return $message->action(__('View your result'), route('cbt.attempts.result', ['tenant' => $this->tenant->slug, 'attempt' => $this->attempt->id]));
    }
}
