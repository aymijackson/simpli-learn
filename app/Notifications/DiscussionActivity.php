<?php

namespace App\Notifications;

use App\Models\Tenant;
use App\Notifications\Concerns\ShowsInApp;
use Elibrary\Lms\Models\CourseDiscussion;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/** In-app only: a new course question, or a reply in a thread you're part of. */
class DiscussionActivity extends Notification
{
    use ShowsInApp;

    public function __construct(public CourseDiscussion $discussion, public string $kind, public string $by, public Tenant $tenant)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    protected function inApp(object $notifiable): array
    {
        $course = $this->discussion->course;
        $title = Str::limit($this->discussion->title, 80);

        return [
            'title' => $this->kind === 'new' ? __('New question in :course', ['course' => $course->title]) : __('New reply: :title', ['title' => $title]),
            'body' => $this->kind === 'new' ? __(':by asked: :title', ['by' => $this->by, 'title' => $title]) : __(':by replied in :course.', ['by' => $this->by, 'course' => $course->title]),
            'url' => route('lms.discussions.show', ['tenant' => $this->tenant->slug, 'course' => $course->slug, 'discussion' => $this->discussion->id]),
            'icon' => 'chat',
            'tone' => 'indigo',
        ];
    }
}
