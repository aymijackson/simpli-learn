<?php

namespace App\Notifications;

use App\Models\Tenant;
use Elibrary\Lms\Models\CourseAssignment;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CourseAssignmentReminder extends Notification
{
    public function __construct(public CourseAssignment $assignment, public Tenant $tenant, public int $progress)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
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
