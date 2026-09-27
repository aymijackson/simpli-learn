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
            'title' => __('New course assigned'),
            'body' => $this->assignment->due_at
                ? __(':by assigned you :course, due :date.', ['by' => $this->assignedBy, 'course' => $course->title, 'date' => $this->assignment->due_at->translatedFormat('j M')])
                : __(':by assigned you :course.', ['by' => $this->assignedBy, 'course' => $course->title]),
            'url' => route('lms.courses.show', ['tenant' => $this->tenant->slug, 'course' => $course->slug]),
            'icon' => 'academic-cap',
            'tone' => 'indigo',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $course = $this->assignment->course;
        $message = (new MailMessage)
            ->subject(__('New course assigned: :course', ['course' => $course->title]))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name]))
            ->line(__(':by has assigned you the course **:course** on :workspace.', ['by' => $this->assignedBy, 'course' => $course->title, 'workspace' => $this->tenant->name]));

        if ($this->assignment->due_at) {
            $message->line(__('Please complete it by **:date**.', ['date' => $this->assignment->due_at->translatedFormat('l, j F Y')]));
        }

        return $message
            ->line(__("You're already enrolled — just sign in and start. Your progress is saved as you go."))
            ->action(__('Start the course'), route('lms.courses.show', ['tenant' => $this->tenant->slug, 'course' => $course->slug]));
    }
}
