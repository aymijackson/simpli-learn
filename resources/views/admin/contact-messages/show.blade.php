<x-admin-layout :title="$contact->subject ?: 'Contact message'">
    <a href="{{ route('admin.contact-messages.index') }}" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-800">
        <x-icon name="arrow-left" class="h-4 w-4" /> Contact messages
    </a>

    <x-card>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <h1 class="text-xl font-bold text-slate-900">{{ $contact->subject ?: 'No subject' }}</h1>
                <p class="mt-1 text-sm text-slate-600">{{ $contact->name }} &lt;<a href="mailto:{{ rawurlencode($contact->email) }}" class="text-brand-700 hover:underline">{{ $contact->email }}</a>&gt;</p>
                <p class="mt-0.5 text-xs text-slate-500">{{ $contact->created_at->format('D j M Y, H:i') }}</p>
            </div>
            <div class="flex shrink-0 gap-2">
                <x-button :href="'mailto:'.rawurlencode($contact->email).'?subject='.rawurlencode('Re: '.($contact->subject ?: 'Your message'))" icon="mail">Reply by email</x-button>
                <form method="POST" action="{{ route('admin.contact-messages.destroy', $contact) }}" onsubmit="return confirm('Delete this message?')">
                    @csrf
                    @method('DELETE')
                    <x-button type="submit" variant="ghost" class="text-red-700">Delete</x-button>
                </form>
            </div>
        </div>
        {{-- Always escaped plain text — never rendered as HTML. --}}
        <div class="mt-6 border-t border-slate-100 pt-6 text-[15px] leading-relaxed whitespace-pre-line text-slate-800">{{ $contact->message }}</div>
    </x-card>
</x-admin-layout>
