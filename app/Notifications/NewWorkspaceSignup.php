<?php

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Tells central admins that a new workspace is waiting for review. */
class NewWorkspaceSignup extends Notification
{
    public function __construct(public Tenant $tenant, public string $ownerName, public string $ownerEmail)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $modules = $this->tenant->tenantModules->map(fn ($tenantModule) => $tenantModule->module->label())->implode(', ');

        return (new MailMessage)
            ->subject("New workspace awaiting approval: {$this->tenant->name}")
            ->line("**{$this->tenant->name}** (/t/{$this->tenant->slug}) has signed up and is waiting for review.")
            ->line("Owner: {$this->ownerName} <{$this->ownerEmail}>")
            ->line("Modules: {$modules}")
            ->action('Review it', route('admin.tenants.show', $this->tenant));
    }
}
