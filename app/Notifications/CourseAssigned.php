<?php

namespace App\Notifications;

use App\Models\Tenant;
use Elibrary\Lms\Models\CourseAssignment;
use App\Notifications\Concerns\ShowsInApp;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CourseAssigned extends Notification
{
    use ShowsInApp;

    public function __construct(public CourseAssignment $assignment, public Tenant $tenant, public string $assignedBy)
    {
    }

    public function via(object $notifiable): array
    {
        // In-app first, so it's recorded even if the mail server is down.
        return ['database', 'mail'];
    }

    protected function inApp(object $notifiable): array
    {
        $course = $this->assignment->course;

        return [
            'title' => 'New course assigned',
            'body' => "{$this->assignedBy} assigned you {$course->title}".($this->assignment->due_at ? ', due '.$this->assignment->due_at->format('j M') : '').'.',
            'url' => route('lms.courses.show', ['tenant' => $this->tenant->slug, 'course' => $course->slug]),
            'icon' => 'academic-cap',
            'tone' => 'indigo',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $course = $this->assignment->course;
        $message = (new MailMessage)
            ->subject("New course assigned: {$course->title}")
            ->greeting("Hello {$notifiable->name},")
            ->line("{$this->assignedBy} has assigned you the course **{$course->title}** on {$this->tenant->name}.");

        if ($this->assignment->due_at) {
            $message->line('Please complete it by **'.$this->assignment->due_at->format('l, j F Y').'**.');
        }

        return $message
            ->line("You're already enrolled — just sign in and start. Your progress is saved as you go.")
            ->action('Start the course', route('lms.courses.show', ['tenant' => $this->tenant->slug, 'course' => $course->slug]));
    }
}
