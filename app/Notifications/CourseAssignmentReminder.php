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
            'title' => $overdue ? __('Course overdue') : __('Course due soon'),
            'body' => __($overdue ? ":course was due :date — you're :progress% through." : ":course is due :date — you're :progress% through.", ['course' => $course->title, 'date' => $this->assignment->due_at->translatedFormat('j M'), 'progress' => $this->progress]),
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
            ->subject($overdue ? __('Overdue: :course', ['course' => $course->title]) : __('Reminder: :course is due :date', ['course' => $course->title, 'date' => $due->translatedFormat('j M')]))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name]))
            ->line($overdue
                ? __('The course **:course** was due on :date and is not finished yet.', ['course' => $course->title, 'date' => $due->translatedFormat('l, j F Y')])
                : __('The course **:course** is due on **:date**.', ['course' => $course->title, 'date' => $due->translatedFormat('l, j F Y')]))
            ->line(__("You're :progress% of the way through.", ['progress' => $this->progress]))
            ->action($this->progress > 0 ? __('Continue the course') : __('Start the course'), route('lms.courses.show', ['tenant' => $this->tenant->slug, 'course' => $course->slug]))
            ->line($course->final_exam_id
                ? __("If you've already finished, make sure every lesson is marked complete and the final assessment is passed.")
                : __("If you've already finished, make sure every lesson is marked complete."));
    }
}
