<?php

namespace App\View\Components;

use App\Enums\TenantStatus;
use App\Models\ContactMessage;
use App\Models\SupportConversation;
use App\Models\Tenant;
use Illuminate\View\Component;

class AdminLayout extends Component
{
    /** Workspaces waiting for approval, shown as a sidebar badge. */
    public int $pendingTenants;

    /** Workspace owners' support chats waiting for a reply. */
    public int $supportWaiting;

    /** Contact-form messages not yet opened. */
    public int $contactUnread;

    public function __construct(public ?string $title = null)
    {
        $this->pendingTenants = Tenant::where('status', TenantStatus::Pending)->count();
        $this->supportWaiting = SupportConversation::withoutGlobalScopes()->where('desk', SupportConversation::PLATFORM)->awaitingStaff()->count();
        $this->contactUnread = ContactMessage::whereNull('read_at')->count();
    }

    public function render()
    {
        return view('components.admin-layout');
    }
}
