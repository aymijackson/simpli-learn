<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/** Platform admins read and delete contact-form messages. */
class ContactMessageController extends Controller
{
    public function index(): View
    {
        return view('admin.contact-messages.index', [
            'messages' => ContactMessage::latest()->paginate(30),
        ]);
    }

    public function show(ContactMessage $contactMessage): View
    {
        if (! $contactMessage->read_at) {
            $contactMessage->forceFill(['read_at' => now()])->save();
        }

        return view('admin.contact-messages.show', ['contact' => $contactMessage]);
    }

    public function destroy(ContactMessage $contactMessage): RedirectResponse
    {
        $contactMessage->delete();
        ActivityLog::record('contact.deleted', 'Deleted a contact-form message');

        return redirect()->route('admin.contact-messages.index')->with('status', 'Message deleted.');
    }
}
