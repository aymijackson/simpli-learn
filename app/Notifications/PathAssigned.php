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
            'title' => 'New learning path assigned',
            'body' => "{$this->assignedBy} assigned you {$this->path->title} ({$this->path->courses->count()} courses)".($this->dueAt ? ', due '.$this->dueAt->format('j M') : '').'.',
            'url' => $this->url(),
            'icon' => 'map',
            'tone' => 'indigo',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject("New learning path: {$this->path->title}")
            ->greeting("Hello {$notifiable->name},")
            ->line("{$this->assignedBy} has assigned you the learning path **{$this->path->title}** on {$this->tenant->name}. It has {$this->path->courses->count()} courses:");

        foreach ($this->path->courses as $index => $course) {
            $message->line(($index + 1).'. '.$course->title);
        }

        if ($this->dueAt) {
            $message->line('Please complete it by **'.$this->dueAt->format('l, j F Y').'**.');
        }

        if ($this->path->is_sequential) {
            $message->line('The courses open in order, each once the one before it is passed.');
        }

        return $message->action('Open the path', $this->url());
    }

    private function url(): string
    {
        return route('lms.paths.show', ['tenant' => $this->tenant->slug, 'path' => $this->path->slug]);
    }
}
