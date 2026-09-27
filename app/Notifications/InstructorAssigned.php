<?php

namespace App\Notifications;

use App\Models\Tenant;
use App\Notifications\Concerns\ShowsInApp;
use Elibrary\Lms\Models\Course;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InstructorAssigned extends Notification
{
    use ShowsInApp;

    public function __construct(public Course $course, public Tenant $tenant, public string $assignedBy)
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
            'title' => __('You now teach a course'),
            'body' => __(':by made you an instructor on :course.', ['by' => $this->assignedBy, 'course' => $this->course->title]),
            'url' => $this->url(),
            'icon' => 'academic-cap',
            'tone' => 'brand',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__("You're teaching :course", ['course' => $this->course->title]))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name]))
            ->line(__(':by has made you an instructor on **:course** at :workspace.', ['by' => $this->assignedBy, 'course' => $this->course->title, 'workspace' => $this->tenant->name]))
            ->line(__("You can now edit its lessons, follow learners' progress and mark written exam answers. Sign-in needs two-step login for this, so you may be asked to turn it on first."))
            ->action(__('Open the course'), $this->url());
    }

    private function url(): string
    {
        return route('lms.manage.courses.edit', ['tenant' => $this->tenant->slug, 'course' => $this->course->slug]);
    }
}
