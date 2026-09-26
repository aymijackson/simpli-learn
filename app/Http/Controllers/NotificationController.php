<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The signed-in person's in-app notifications (the bell). Opening one marks
 * it read and follows its link.
 */
class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        return view('notifications.index', [
            'notifications' => $request->user()->notifications()->paginate(20),
            'unread' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function open(Request $request, string $tenant, string $notification): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($notification);
        $notification->markAsRead();

        $url = $notification->data['url'] ?? null;

        // Only ever follow links back into this site.
        $host = $url ? parse_url($url, PHP_URL_HOST) : null;
        if ($url && ($host === null || $host === $request->getHost())) {
            return redirect()->to($url);
        }

        return redirect()->route('tenant.notifications.index');
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('status', 'All notifications marked as read.');
    }
}
