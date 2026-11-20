<?php

namespace App\Support;

use App\Enums\TenantStatus;
use App\Models\Scopes\TenantScope;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

/**
 * Finds a person's accounts across workspaces, for the central login page
 * ("we'll take you to your workspace") and the "forgot your workspace?"
 * email. People often remember their email and password but not which
 * workspace address their organisation uses.
 *
 * Only accounts in active workspaces count. Callers must never reveal which
 * workspaces an email belongs to without proof of ownership: a correct
 * password (sign-in) or the mailbox itself (the emailed list).
 */
class WorkspaceAccounts
{
    /** @return Collection<int, User> workspace accounts for this email, tenant loaded, oldest first */
    public static function forEmail(string $email): Collection
    {
        return User::withoutGlobalScope(TenantScope::class)
            ->whereNotNull('tenant_id')
            ->where('email', $email)
            ->whereHas('tenant', fn ($query) => $query->where('status', TenantStatus::Active))
            ->with('tenant')
            ->orderBy('id')
            ->get();
    }

    /** @return Collection<int, User> the accounts above whose password matches */
    public static function matching(string $email, string $password): Collection
    {
        return self::forEmail($email)
            ->filter(fn (User $user) => $user->password !== null && Hash::check($password, $user->password))
            ->values();
    }
}
