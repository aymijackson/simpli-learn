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
            'title' => 'You now teach a course',
            'body' => "{$this->assignedBy} made you an instructor on {$this->course->title}.",
            'url' => $this->url(),
            'icon' => 'academic-cap',
            'tone' => 'brand',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("You're teaching {$this->course->title}")
            ->greeting("Hello {$notifiable->name},")
            ->line("{$this->assignedBy} has made you an instructor on **{$this->course->title}** at {$this->tenant->name}.")
            ->line('You can now edit its lessons, follow learners\' progress and mark written exam answers. Sign-in needs two-step login for this, so you may be asked to turn it on first.')
            ->action('Open the course', $this->url());
    }

    private function url(): string
    {
        return route('lms.manage.courses.edit', ['tenant' => $this->tenant->slug, 'course' => $this->course->slug]);
    }
}
