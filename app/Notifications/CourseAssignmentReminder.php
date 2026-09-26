<?php

namespace App\Notifications;

use App\Models\Tenant;
use Elibrary\Lms\Models\CourseAssignment;
use App\Notifications\Concerns\ShowsInApp;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CourseAssignmentReminder extends Notification
{
    use ShowsInApp;

    public function __construct(public CourseAssignment $assignment, public Tenant $tenant, public int $progress)
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
        $overdue = $this->assignment->due_at->isPast();

        return [
            'title' => $overdue ? 'Course overdue' : 'Course due soon',
            'body' => "{$course->title} ".($overdue ? 'was' : 'is').' due '.$this->assignment->due_at->format('j M')." — you're {$this->progress}% through.",
            'url' => route('lms.courses.show', ['tenant' => $this->tenant->slug, 'course' => $course->slug]),
            'icon' => 'clock',
            'tone' => $overdue ? 'rose' : 'amber',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $course = $this->assignment->course;
        $due = $this->assignment->due_at;
        $overdue = $due->isPast();

        return (new MailMessage)
            ->subject($overdue ? "Overdue: {$course->title}" : "Reminder: {$course->title} is due ".$due->format('j M'))
            ->greeting("Hello {$notifiable->name},")
            ->line($overdue
                ? "The course **{$course->title}** was due on ".$due->format('l, j F Y').' and is not finished yet.'
                : "The course **{$course->title}** is due on **".$due->format('l, j F Y').'**.')
            ->line("You're {$this->progress}% of the way through.")
            ->action($this->progress > 0 ? 'Continue the course' : 'Start the course', route('lms.courses.show', ['tenant' => $this->tenant->slug, 'course' => $course->slug]))
            ->line("If you've already finished, make sure every lesson is marked complete".($course->final_exam_id ? ' and the final assessment is passed' : '').'.');
    }
}
