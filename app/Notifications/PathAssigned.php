<?php

namespace App\Notifications;

use App\Models\Tenant;
use App\Notifications\Concerns\ShowsInApp;
use Elibrary\Lms\Models\LearningPath;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class PathAssigned extends Notification
{
    use ShowsInApp;

    public function __construct(public LearningPath $path, public Tenant $tenant, public string $assignedBy, public ?Carbon $dueAt = null)
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
            'title' => __('New learning path assigned'),
            'body' => $this->dueAt
                ? __(':by assigned you :path (:count courses), due :date.', ['by' => $this->assignedBy, 'path' => $this->path->title, 'count' => $this->path->courses->count(), 'date' => $this->dueAt->translatedFormat('j M')])
                : __(':by assigned you :path (:count courses).', ['by' => $this->assignedBy, 'path' => $this->path->title, 'count' => $this->path->courses->count()]),
            'url' => $this->url(),
            'icon' => 'map',
            'tone' => 'indigo',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject(__('New learning path: :path', ['path' => $this->path->title]))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name]))
            ->line(__(':by has assigned you the learning path **:path** on :workspace. It has :count courses:', ['by' => $this->assignedBy, 'path' => $this->path->title, 'workspace' => $this->tenant->name, 'count' => $this->path->courses->count()]));

        foreach ($this->path->courses as $index => $course) {
            $message->line(($index + 1).'. '.$course->title);
        }

        if ($this->dueAt) {
            $message->line(__('Please complete it by **:date**.', ['date' => $this->dueAt->translatedFormat('l, j F Y')]));
        }

        if ($this->path->is_sequential) {
            $message->line(__('The courses open in order, each once the one before it is passed.'));
        }

        return $message->action(__('Open the path'), $this->url());
    }

    private function url(): string
    {
        return route('lms.paths.show', ['tenant' => $this->tenant->slug, 'path' => $this->path->slug]);
    }
}
