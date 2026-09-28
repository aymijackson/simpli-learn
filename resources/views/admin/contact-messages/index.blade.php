<x-admin-layout title="Contact messages">
    <x-page-header title="Contact messages" subtitle="Sent from the public contact form. Stored encrypted and deleted automatically after a year." />

    @if (session('status'))
        <div role="status" class="mb-6 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800 ring-1 ring-inset ring-emerald-200">{{ session('status') }}</div>
    @endif

    @if ($messages->isEmpty())
        <x-empty-state icon="mail" title="No messages yet" description="Messages from the website's contact form will appear here." />
    @else
        <x-card :padded="false">
            <ul class="divide-y divide-slate-100">
                @foreach ($messages as $contact)
                    <li>
                        <a href="{{ route('admin.contact-messages.show', $contact) }}" class="flex items-start justify-between gap-4 px-5 py-4 hover:bg-slate-50">
                            <span class="min-w-0">
                                <span class="flex items-center gap-2">
                                    @unless ($contact->read_at)<span class="h-2 w-2 shrink-0 rounded-full bg-brand-600" aria-label="Unread"></span>@endunless
                                    <span class="truncate text-sm {{ $contact->read_at ? 'font-medium text-slate-700' : 'font-semibold text-slate-900' }}">{{ $contact->subject ?: 'No subject' }}</span>
                                </span>
                                <span class="mt-0.5 block truncate text-xs text-slate-500">{{ $contact->name }} &middot; {{ $contact->email }} &middot; {{ \Illuminate\Support\Str::limit($contact->message, 80) }}</span>
                            </span>
                            <span class="shrink-0 text-xs text-slate-500">{{ $contact->created_at->diffForHumans() }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </x-card>
        <div class="mt-4">{{ $messages->links() }}</div>
    @endif
</x-admin-layout>
