<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Notifications\YourWorkspaces;
use App\Support\WorkspaceAccounts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

/**
 * "Forgot your workspace?" — emails someone links to every workspace their
 * address belongs to. The reply on screen is the same whether or not any
 * were found, so the page can't be used to discover who has an account.
 */
class ForgotWorkspaceController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-workspace');
    }

    public function store(Request $request): RedirectResponse
    {
        $email = $request->validate(['email' => ['required', 'email', 'max:255']])['email'];

        $workspaces = WorkspaceAccounts::forEmail($email)
            ->reject->isDeactivated()
            ->pluck('tenant')
            ->unique('id')
            ->values();

        if ($workspaces->isNotEmpty()) {
            Notification::route('mail', $email)->notify(new YourWorkspaces($workspaces));
        }

        return back()->with('status', __("If that email belongs to any workspaces, we've sent you a link to each one. Check your inbox (and spam folder)."));
    }
}
