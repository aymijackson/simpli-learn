<?php

namespace App\Notifications;

use App\Enums\Badge;
use App\Models\Tenant;
use App\Notifications\Concerns\ShowsInApp;
use Illuminate\Notifications\Notification;

class BadgeEarned extends Notification
{
    use ShowsInApp;

    public function __construct(public Badge $badge, public Tenant $tenant)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    protected function inApp(object $notifiable): array
    {
        return [
            'title' => 'Badge earned: '.$this->badge->label(),
            'body' => $this->badge->description(),
            'url' => route('tenant.home', ['tenant' => $this->tenant->slug]).'#achievements',
            'icon' => $this->badge->icon(),
            'tone' => 'amber',
        ];
    }
}
