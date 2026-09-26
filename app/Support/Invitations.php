<?php

namespace App\Support;

use App\Models\Tenant;
use App\Models\User;
use App\Notifications\AddedToWorkspace;
use Illuminate\Support\Facades\Password;

/**
 * Invitation emails with a "set your password" link, so owners never have
 * to choose (or send) anyone's password. Links use the `invites` password
 * broker, which lasts 7 days instead of the 60 minutes of a reset link.
 */
class Invitations
{
    public const BROKER = 'invites';

    public static function send(User $user, Tenant $tenant, string $invitedBy): bool
    {
        $token = Password::broker(self::BROKER)->createToken($user);

        $url = route('tenant.password.reset', [
            'tenant' => $tenant->slug,
            'token' => $token,
            'email' => $user->email,
            'invite' => 1,
        ]);

        return SafeNotifier::send($user, new AddedToWorkspace($tenant, $invitedBy, $url));
    }
}
