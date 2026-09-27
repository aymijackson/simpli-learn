<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/** An error alert for platform admins (see ErrorAlerter). */
class ErrorOccurred extends Notification
{
    /** @param  array<string, string>  $summary */
    public function __construct(public array $summary)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->error()
            ->subject('['.config('app.name').'] Error: '.Str::limit(class_basename($this->summary['error']).' — '.($this->summary['message'] ?? ''), 120))
            ->greeting('Something went wrong')
            ->line('An error was reported on '.($this->summary['app'] ?? 'the platform').'. The details are in the log (storage/logs); here is a summary:');

        foreach (['error' => 'Error', 'message' => 'Message', 'where' => 'Where', 'page' => 'Page', 'workspace' => 'Workspace', 'user' => 'User', 'occurrences' => 'Repeats', 'time' => 'Time'] as $key => $label) {
            if (isset($this->summary[$key])) {
                $message->line("**{$label}:** ".e($this->summary[$key]));
            }
        }

        return $message->line('You get at most one email per error every '.config('monitoring.alert_throttle_minutes').' minutes.');
    }
}
