<?php

namespace App\Notifications\Concerns;

/**
 * Notifications that also appear under the bell. The stored payload is a
 * small, display-ready summary — the bell never re-reads the models, so a
 * deleted course or exam can't break someone's notification list.
 */
trait ShowsInApp
{
    /** @return array{title: string, body: string, url: string, icon: string, tone: string} */
    abstract protected function inApp(object $notifiable): array;

    public function toDatabase(object $notifiable): array
    {
        return $this->inApp($notifiable);
    }
}
